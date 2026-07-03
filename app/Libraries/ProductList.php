<?php

namespace App\Libraries;

class ProductList
{
    public const DEFAULT_CONFIG = [
        'products' => [
            [
                'id'          => 'product_1',
                'name'        => 'Product Name',
                'description' => '',
                'price'       => 0.0,
                'stock'       => 10,
                'image'       => null,
            ],
        ],
    ];

    public static function sanitizeConfig($raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $rows = is_array($raw['products'] ?? null) ? $raw['products'] : [];

        $products = [];
        $seenIds = [];

        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = self::sanitizeProductId((string) ($row['id'] ?? ''), $i + 1);
            while (isset($seenIds[$id])) {
                $id = 'product_' . ($i + 1) . '_' . count($seenIds);
            }
            $seenIds[$id] = true;

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $name = 'Product Name';
            }

            $products[] = [
                'id'          => $id,
                'name'        => mb_substr($name, 0, 120),
                'description' => mb_substr(trim((string) ($row['description'] ?? '')), 0, 500),
                'price'       => self::money((float) ($row['price'] ?? 0)),
                'stock'       => self::clampInt($row['stock'] ?? 0, 0, 999999),
                'image'       => self::sanitizeImageName($row['image'] ?? null),
            ];
        }

        return ['products' => $products !== [] ? $products : self::DEFAULT_CONFIG['products']];
    }

    /**
     * @return array{0:?string,1:?string}
     */
    public static function selectionValue(array $field, $submitted): array
    {
        $submitted = is_array($submitted) ? $submitted : [];
        $selected = $submitted['selected'] ?? [];
        $selected = is_array($selected) ? array_values(array_unique(array_map('strval', $selected))) : [];
        $qtyById = is_array($submitted['qty'] ?? null) ? $submitted['qty'] : [];

        $products = self::sanitizeConfig($field['options'] ?? [])['products'];
        $byId = [];
        foreach ($products as $product) {
            $byId[$product['id']] = $product;
        }

        $items = [];
        $total = 0.0;

        foreach ($selected as $id) {
            if (! isset($byId[$id])) {
                return [null, 'Invalid product selection for ' . $field['label'] . '.'];
            }

            $product = $byId[$id];
            $qty = self::clampInt($qtyById[$id] ?? 0, 0, 999999);
            if ($qty < 1) {
                return [null, 'Please choose a valid quantity for ' . $product['name'] . '.'];
            }
            if ((int) $product['stock'] <= 0) {
                return [null, $product['name'] . ' is out of stock.'];
            }
            if ($qty > (int) $product['stock']) {
                return [null, 'Only ' . $product['stock'] . ' left for ' . $product['name'] . '.'];
            }

            $lineTotal = self::money((float) $product['price'] * $qty);
            $total += $lineTotal;
            $items[] = [
                'id'         => $product['id'],
                'name'       => $product['name'],
                'price'      => self::money((float) $product['price']),
                'quantity'   => $qty,
                'line_total' => $lineTotal,
            ];
        }

        if ($items === []) {
            return [(bool) ($field['is_required'] ?? false) ? null : null, (bool) ($field['is_required'] ?? false) ? ($field['label'] . ' is required.') : null];
        }

        return [json_encode(['items' => $items, 'total' => self::money($total)], JSON_UNESCAPED_SLASHES), null];
    }

    public static function selectionTotal(array $field, $submitted): ?float
    {
        [$value, $error] = self::selectionValue($field, $submitted);
        if ($error !== null || $value === null) {
            return null;
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded) || ! array_key_exists('total', $decoded)) {
            return null;
        }

        return self::money((float) $decoded['total']);
    }

    public static function decrementStock(array $config, string $selectionJson): array
    {
        $selection = json_decode($selectionJson, true);
        if (! is_array($selection) || ! is_array($selection['items'] ?? null)) {
            return self::sanitizeConfig($config);
        }

        $qtyById = [];
        foreach ($selection['items'] as $item) {
            if (is_array($item) && isset($item['id'])) {
                $qtyById[(string) $item['id']] = (int) ($qtyById[(string) $item['id']] ?? 0) + max(0, (int) ($item['quantity'] ?? 0));
            }
        }

        $config = self::sanitizeConfig($config);
        foreach ($config['products'] as &$product) {
            $product['stock'] = max(0, (int) $product['stock'] - (int) ($qtyById[$product['id']] ?? 0));
        }
        unset($product);

        return $config;
    }

    public static function incrementStock(array $config, string $selectionJson): array
    {
        $selection = json_decode($selectionJson, true);
        if (! is_array($selection) || ! is_array($selection['items'] ?? null)) {
            return self::sanitizeConfig($config);
        }

        $qtyById = [];
        foreach ($selection['items'] as $item) {
            if (is_array($item) && isset($item['id'])) {
                $qtyById[(string) $item['id']] = (int) ($qtyById[(string) $item['id']] ?? 0) + max(0, (int) ($item['quantity'] ?? 0));
            }
        }

        $config = self::sanitizeConfig($config);
        foreach ($config['products'] as &$product) {
            $product['stock'] = self::clampInt((int) $product['stock'] + (int) ($qtyById[$product['id']] ?? 0), 0, 999999);
        }
        unset($product);

        return $config;
    }

    public static function formatAnswer(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded) || ! is_array($decoded['items'] ?? null) || ! array_key_exists('total', $decoded)) {
            return null;
        }

        $parts = [];
        foreach ($decoded['items'] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? 'Product'));
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $lineTotal = self::money((float) ($item['line_total'] ?? 0));
            $parts[] = $name . ' x' . $qty . ' (' . self::formatMoney($lineTotal) . ')';
        }

        if ($parts === []) {
            return '';
        }

        $parts[] = 'Total ' . self::formatMoney((float) $decoded['total']);

        return implode('; ', $parts);
    }

    public static function formatMoney(float $amount): string
    {
        return 'RM' . number_format(self::money($amount), 2, '.', '');
    }

    private static function sanitizeProductId(string $id, int $fallback): string
    {
        $id = strtolower(trim($id));
        if (preg_match('/^product_[a-z0-9_-]{1,64}$/', $id) === 1) {
            return $id;
        }

        return 'product_' . max(1, $fallback);
    }

    private static function sanitizeImageName($name): ?string
    {
        $name = basename((string) $name);
        return preg_match('/^[a-f0-9]{16}\.(jpg|png|gif|webp)$/', $name) === 1 ? $name : null;
    }

    private static function clampInt($value, int $min, int $max): int
    {
        return min($max, max($min, (int) $value));
    }

    private static function money(float $value): float
    {
        return round(min(999999.99, max(0, $value)), 2);
    }
}
