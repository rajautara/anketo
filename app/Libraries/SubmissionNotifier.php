<?php

namespace App\Libraries;

use CodeIgniter\Shield\Models\UserModel;

/**
 * Sends an email to the form owner (or an override address) when a public
 * submission is received. All work is guarded so a mail failure can never
 * break or slow the anonymous submitter's request.
 */
class SubmissionNotifier
{
    /**
     * Notify the recipient of a new submission, if the form has notifications
     * enabled. NEVER throws — every failure is caught and logged.
     *
     * @param array                                                                                   $form         The forms row (incl. notify_on_submission, notification_email, user_id, title, id)
     * @param list<array{field_id:int,field_key:string,field_label:string,value:?string,file_path:?string}> $toSave The answer rows built in PublicFormController::submit()
     */
    public function notify(array $form, int $submissionId, array $toSave): void
    {
        try {
            if (empty($form['notify_on_submission'])) {
                return;
            }

            $recipient = $this->resolveRecipient($form);
            if ($recipient === null) {
                log_message(
                    'warning',
                    'Submission notification skipped for form {id}: no recipient resolvable.',
                    ['id' => $form['id']]
                );

                return;
            }

            $body = view('emails/submission_notification', [
                'formTitle'   => $form['title'],
                'submittedAt' => date('Y-m-d H:i:s'),
                'detailUrl'   => site_url('forms/' . $form['id'] . '/submissions/' . $submissionId),
                'answers'     => array_map([$this, 'formatAnswer'], $toSave),
            ]);

            $email = service('email');
            $email->setTo($recipient);
            $email->setSubject('New submission: ' . $form['title']);
            $email->setMailType('html');
            $email->setMessage($body);

            if (! $email->send(false)) {
                log_message(
                    'error',
                    'Submission notification email failed for form {id}: {debug}',
                    ['id' => $form['id'], 'debug' => $email->printDebugger(['headers'])]
                );
            }
        } catch (\Throwable $e) {
            log_message('error', 'Submission notification exception: {msg}', ['msg' => $e->getMessage()]);
        }
    }

    /**
     * The override email if set, otherwise the form owner's account email.
     */
    private function resolveRecipient(array $form): ?string
    {
        $override = trim((string) ($form['notification_email'] ?? ''));
        if ($override !== '') {
            return $override;
        }

        $owner = (new UserModel())->find($form['user_id']);
        $email = $owner?->getEmailIdentity()?->secret;

        return ($email !== null && $email !== '') ? $email : null;
    }

    /**
     * Mirrors SubmissionController::formatAnswerForCsv() / submissions/show.php:
     * file -> original filename; JSON array -> comma-joined; else raw value.
     *
     * @return array{label:string, value:string, isFile:bool}
     */
    private function formatAnswer(array $row): array
    {
        $label = (string) $row['field_label'];

        if (! empty($row['file_path'])) {
            return ['label' => $label, 'value' => (string) ($row['value'] ?? '(file)'), 'isFile' => true];
        }

        return [
            'label'  => $label,
            'value'  => (new SubmissionAnswerFormatter())->format($row),
            'isFile' => false,
        ];
    }
}
