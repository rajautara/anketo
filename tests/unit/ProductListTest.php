<?php

use App\Libraries\ProductList;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ProductListTest extends CIUnitTestCase
{
    private array $field = [
        'id'          => 10,
        'form_id'     => 5,
        'field_type'  => 'product_list',
        'field_key'   => 'products',
        'label'       => 'Products',
        'is_required' => false,
        'options'     => [
            'products' => [
                ['id' => 'product_a', 'name' => 'Shirt', 'description' => 'Cotton', 'price' => 10, 'stock' => 3, 'image' => '0123456789abcdef.jpg'],
                ['id' => 'product_b', 'name' => 'Hat', 'description' => '', 'price' => 5.5, 'stock' => 0, 'image' => null],
            ],
        ],
    ];

    public function testSanitizeConfigClampsAndKeepsValidImage(): void
    {
        $config = ProductList::sanitizeConfig([
            'products' => [
                ['id' => '../bad', 'name' => '', 'description' => str_repeat('x', 600), 'price' => -1, 'stock' => -5, 'image' => '0123456789abcdef.png'],
            ],
        ]);

        $this->assertSame('product_1', $config['products'][0]['id']);
        $this->assertSame('Product Name', $config['products'][0]['name']);
        $this->assertSame(500, mb_strlen($config['products'][0]['description']));
        $this->assertSame(0.0, $config['products'][0]['price']);
        $this->assertSame(0, $config['products'][0]['stock']);
        $this->assertSame('0123456789abcdef.png', $config['products'][0]['image']);
    }

    public function testSelectionValueStoresServerProductDataAndTotal(): void
    {
        [$value, $error] = ProductList::selectionValue($this->field, [
            'selected' => ['product_a'],
            'qty'      => ['product_a' => '2'],
        ]);

        $this->assertNull($error);
        $decoded = json_decode((string) $value, true);
        $this->assertSame('Shirt', $decoded['items'][0]['name']);
        $this->assertSame(2, $decoded['items'][0]['quantity']);
        $this->assertEqualsWithDelta(20.0, (float) $decoded['total'], 0.001);
    }

    public function testSelectionTotalReturnsNumericTotal(): void
    {
        $total = ProductList::selectionTotal($this->field, [
            'selected' => ['product_a'],
            'qty'      => ['product_a' => '3'],
        ]);

        $this->assertSame(30.0, $total);
    }

    public function testSelectionRejectsQuantityAboveStock(): void
    {
        [, $error] = ProductList::selectionValue($this->field, [
            'selected' => ['product_a'],
            'qty'      => ['product_a' => '4'],
        ]);

        $this->assertSame('Only 3 left for Shirt.', $error);
    }

    public function testRequiredSelectionRejectsEmpty(): void
    {
        $field = $this->field;
        $field['is_required'] = true;

        [, $error] = ProductList::selectionValue($field, []);

        $this->assertSame('Products is required.', $error);
    }

    public function testDecrementStock(): void
    {
        [$value] = ProductList::selectionValue($this->field, [
            'selected' => ['product_a'],
            'qty'      => ['product_a' => '2'],
        ]);

        $config = ProductList::decrementStock($this->field['options'], (string) $value);

        $this->assertSame(1, $config['products'][0]['stock']);
    }

    public function testIncrementStock(): void
    {
        [$value] = ProductList::selectionValue($this->field, [
            'selected' => ['product_a'],
            'qty'      => ['product_a' => '2'],
        ]);

        $decremented = ProductList::decrementStock($this->field['options'], (string) $value);
        $restored = ProductList::incrementStock($decremented, (string) $value);

        $this->assertSame(3, $restored['products'][0]['stock']);
    }

    public function testFormatAnswerOnlyHandlesProductObject(): void
    {
        [$value] = ProductList::selectionValue($this->field, [
            'selected' => ['product_a'],
            'qty'      => ['product_a' => '2'],
        ]);

        $this->assertSame('Shirt x2 (RM20.00); Total RM20.00', ProductList::formatAnswer($value));
        $this->assertNull(ProductList::formatAnswer('["option_1","option_2"]'));
    }
}
