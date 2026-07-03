<?php

namespace App\Libraries;

use App\Models\FormFieldModel;
use App\Models\FormSubmissionModel;
use App\Models\SubmissionDataModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class SubmissionResetter
{
    private BaseConnection $db;
    private FormFieldModel $fieldModel;
    private FormSubmissionModel $submissionModel;
    private SubmissionDataModel $submissionDataModel;

    public function __construct(
        ?BaseConnection $db = null,
        ?FormFieldModel $fieldModel = null,
        ?FormSubmissionModel $submissionModel = null,
        ?SubmissionDataModel $submissionDataModel = null
    ) {
        $this->db                  = $db ?? Database::connect();
        $this->fieldModel          = $fieldModel ?? new FormFieldModel();
        $this->submissionModel     = $submissionModel ?? new FormSubmissionModel();
        $this->submissionDataModel = $submissionDataModel ?? new SubmissionDataModel();
    }

    /**
     * @return array{submissions:int,files:int,file_failures:int}
     */
    public function resetForm(int $formId): array
    {
        $submissions = $this->submissionModel
            ->where('form_id', $formId)
            ->select('id')
            ->findAll();

        $submissionIds = array_map('intval', array_column($submissions, 'id'));

        if ($submissionIds === []) {
            return ['submissions' => 0, 'files' => 0, 'file_failures' => 0];
        }

        $answers = $this->submissionDataModel
            ->whereIn('submission_id', $submissionIds)
            ->findAll();

        $filePaths = $this->submissionFilePaths($answers);

        $this->db->transStart();
        $this->restoreProductStocks($formId, $answers);
        $this->submissionModel
            ->where('form_id', $formId)
            ->whereIn('id', $submissionIds)
            ->delete();
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new RuntimeException('Unable to reset submissions.');
        }

        $fileResult = $this->deleteSubmissionFiles($formId, $filePaths);

        return [
            'submissions'   => count($submissionIds),
            'files'         => $fileResult['deleted'],
            'file_failures' => $fileResult['failures'],
        ];
    }

    /**
     * @param list<array<string,mixed>> $answers
     */
    private function restoreProductStocks(int $formId, array $answers): void
    {
        $fields = $this->fieldModel
            ->where('form_id', $formId)
            ->where('field_type', 'product_list')
            ->findAll();

        if ($fields === []) {
            return;
        }

        $fieldsById = [];
        $fieldsByKey = [];
        foreach ($fields as $field) {
            $fieldsById[(int) $field['id']] = $field;
            $fieldsByKey[(string) $field['field_key']] = $field;
        }

        $updatedConfigs = [];
        foreach ($answers as $answer) {
            $value = (string) ($answer['value'] ?? '');
            if ($value === '') {
                continue;
            }

            $field = null;
            if (! empty($answer['field_id'])) {
                $field = $fieldsById[(int) $answer['field_id']] ?? null;
            }
            if ($field === null && ! empty($answer['field_key'])) {
                $field = $fieldsByKey[(string) $answer['field_key']] ?? null;
            }
            if ($field === null) {
                continue;
            }

            $fieldId = (int) $field['id'];
            $baseConfig = $updatedConfigs[$fieldId] ?? $field['options'] ?? [];
            $updatedConfigs[$fieldId] = ProductList::incrementStock(is_array($baseConfig) ? $baseConfig : [], $value);
        }

        foreach ($updatedConfigs as $fieldId => $config) {
            $this->fieldModel->update($fieldId, ['options' => $config]);
        }
    }

    /**
     * @param list<array<string,mixed>> $answers
     *
     * @return list<string>
     */
    private function submissionFilePaths(array $answers): array
    {
        $paths = [];
        foreach ($answers as $answer) {
            $path = trim((string) ($answer['file_path'] ?? ''));
            if ($path !== '') {
                $paths[$path] = true;
            }
        }

        return array_keys($paths);
    }

    /**
     * @param list<string> $filePaths
     *
     * @return array{deleted:int,failures:int}
     */
    private function deleteSubmissionFiles(int $formId, array $filePaths): array
    {
        $deleted = 0;
        $failures = 0;
        $directory = WRITEPATH . 'uploads/forms/' . $formId;
        $realDirectory = realpath($directory);

        foreach ($filePaths as $filePath) {
            $relative = str_replace('\\', '/', $filePath);
            $expectedPrefix = 'forms/' . $formId . '/';

            if (! str_starts_with($relative, $expectedPrefix) || $relative !== $expectedPrefix . basename($relative)) {
                $failures++;
                log_message('warning', 'Skipped unsafe submission upload path during reset: {path}', ['path' => $filePath]);
                continue;
            }

            $absolute = WRITEPATH . 'uploads/' . $relative;
            if (! is_file($absolute)) {
                continue;
            }

            if ($realDirectory === false) {
                $failures++;
                log_message('warning', 'Unable to resolve submission upload directory during reset: {dir}', ['dir' => $directory]);
                continue;
            }

            $realFile = realpath($absolute);
            $normalizedDirectory = rtrim(strtolower($realDirectory), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $normalizedFile = $realFile === false ? '' : strtolower($realFile);

            if ($realFile === false || ! str_starts_with($normalizedFile, $normalizedDirectory)) {
                $failures++;
                log_message('warning', 'Skipped submission upload outside form directory during reset: {path}', ['path' => $filePath]);
                continue;
            }

            if (@unlink($realFile)) {
                $deleted++;
                continue;
            }

            $failures++;
            log_message('warning', 'Unable to delete submission upload during reset: {path}', ['path' => $realFile]);
        }

        if (is_dir($directory) && count(scandir($directory) ?: []) <= 2) {
            @rmdir($directory);
        }

        return ['deleted' => $deleted, 'failures' => $failures];
    }
}
