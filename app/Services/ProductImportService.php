<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Parses, validates and imports the admin product CSV format.
 *
 * The controller first renders a dry-run preview from validate(), then calls
 * import() only when the operator confirms. Keeping the logic here makes both
 * steps testable without HTTP requests.
 */
class ProductImportService
{
    public const MAX_ROWS = 500;

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AuditService $audit,
    ) {}

    /**
     * @return array{rows: array<int, array<string, mixed>>, total: int, truncated: bool}
     */
    public function parse(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new RuntimeException('The uploaded CSV could not be read.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new RuntimeException('The uploaded CSV is empty.');
        }

        $header = array_map(function ($value) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);

            return Str::snake(trim($value));
        }, $header);

        if (! in_array('name', $header, true) || ! in_array('regular_price', $header, true)) {
            fclose($handle);
            throw new RuntimeException('The CSV must contain "name" and "regular_price" columns.');
        }

        $rows = [];
        $line = 1;
        $truncated = false;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            if (count($values) === 1 && trim((string) $values[0]) === '') {
                continue;
            }

            $values = array_pad($values, count($header), null);
            $row = array_combine($header, array_slice($values, 0, count($header)));

            if (! is_array($row)) {
                continue;
            }

            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
            $row['_line'] = $line;
            $rows[] = $row;

            if (count($rows) >= self::MAX_ROWS) {
                // Peek once so an oversized file is rejected rather than
                // silently importing only its first 500 rows.
                $next = fgetcsv($handle);
                $truncated = $next !== false
                    && ! (count($next) === 1 && trim((string) $next[0]) === '');

                break;
            }
        }

        fclose($handle);

        return ['rows' => $rows, 'total' => count($rows), 'truncated' => $truncated];
    }

    /**
     * Validate parsed rows, returning normalised valid rows and per-line errors.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{valid: array<int, array<string, mixed>>, errors: array<int, array{line: int, messages: array<int, string>}>, total: int}
     */
    public function validate(array $rows): array
    {
        $valid = [];
        $errors = [];
        $seenSkus = [];
        $categoryCache = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_line'] ?? 0);
            $messages = [];

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $messages[] = 'Name is required.';
            } elseif (mb_strlen($name) > 200) {
                $messages[] = 'Name must be 200 characters or fewer.';
            }

            $regularPrice = $this->number($row['regular_price'] ?? null);
            if ($regularPrice === null || $regularPrice < 0) {
                $messages[] = 'Regular price must be a number greater than or equal to 0.';
            }

            $salePrice = $this->number($row['sale_price'] ?? null);
            if ($salePrice !== null && ($salePrice < 0 || ($regularPrice !== null && $salePrice >= $regularPrice))) {
                $messages[] = 'Sale price must be lower than the regular price.';
            }

            $costPrice = $this->number($row['cost_price'] ?? null);
            if ($costPrice !== null && $costPrice < 0) {
                $messages[] = 'Cost price must be greater than or equal to 0.';
            }

            $statusValue = strtolower(trim((string) ($row['status'] ?? 'draft')));
            $statusValue = $statusValue === '' ? 'draft' : $statusValue;
            $status = ProductStatus::tryFrom($statusValue);
            if (! $status) {
                $messages[] = 'Status must be draft, active or archived.';
            }

            $stock = $this->integer($row['stock'] ?? $row['stock_quantity'] ?? null);
            if ($stock === null) {
                $stock = 0;
            }
            if ($stock < 0) {
                $messages[] = 'Stock must be a whole number greater than or equal to 0.';
            }

            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku !== '') {
                if (isset($seenSkus[$sku])) {
                    $messages[] = "SKU {$sku} is duplicated in this file (first seen on line {$seenSkus[$sku]}).";
                } elseif (Product::withTrashed()->where('sku', $sku)->exists()) {
                    $messages[] = "SKU {$sku} already exists.";
                } else {
                    $seenSkus[$sku] = $line;
                }
            }

            $categoryName = trim((string) ($row['category'] ?? ''));
            $categoryId = null;
            if ($categoryName !== '') {
                $cacheKey = mb_strtolower($categoryName);
                if (! array_key_exists($cacheKey, $categoryCache)) {
                    $categoryCache[$cacheKey] = Category::where('slug', $categoryName)
                        ->orWhere('name', $categoryName)
                        ->first();
                }

                $category = $categoryCache[$cacheKey];
                if (! $category) {
                    $messages[] = "Category \"{$categoryName}\" was not found.";
                } else {
                    $categoryId = $category->id;
                }
            }

            if ($messages !== []) {
                $errors[] = ['line' => $line, 'messages' => $messages];

                continue;
            }

            $valid[] = [
                'line' => $line,
                'name' => $name,
                'sku' => $sku !== '' ? $sku : null,
                'category_id' => $categoryId,
                'category' => $categoryName,
                'regular_price' => $regularPrice,
                'sale_price' => $salePrice,
                'cost_price' => $costPrice,
                'status' => $status->value,
                'stock' => $stock,
                'short_description' => $this->nullableString($row['short_description'] ?? null, 500),
                'description' => $this->nullableString($row['description'] ?? null),
            ];
        }

        return ['valid' => $valid, 'errors' => $errors, 'total' => count($rows)];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function import(array $rows, ?User $actor = null): int
    {
        if ($rows === []) {
            return 0;
        }

        return DB::transaction(function () use ($rows, $actor) {
            $imported = 0;
            $canSetCost = $actor?->can('products.adjust_cost') ?? false;

            foreach ($rows as $row) {
                $product = Product::create([
                    'name' => $row['name'],
                    'sku' => $row['sku'],
                    'category_id' => $row['category_id'],
                    'short_description' => $row['short_description'] ?? null,
                    'description' => $row['description'] ?? null,
                    'regular_price' => $row['regular_price'],
                    'sale_price' => $row['sale_price'],
                    'cost_price' => $canSetCost ? $row['cost_price'] : null,
                    'status' => $row['status'],
                    'has_variants' => true,
                    'variant_attributes' => ['Default'],
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                ]);

                // All stock is tracked on a default variant so the ledger and
                // low-stock alerts behave exactly like manually created products.
                $variant = $product->variants()->create([
                    'title' => 'Default',
                    'attributes' => null,
                    'regular_price' => $row['regular_price'],
                    'sale_price' => $row['sale_price'],
                    'cost_price' => $canSetCost ? $row['cost_price'] : null,
                    'stock_quantity' => 0,
                    'low_stock_threshold' => 5,
                    'is_default' => true,
                    'status' => 'active',
                ]);

                if ((int) ($row['stock'] ?? 0) > 0) {
                    $this->inventory->adjust(
                        $variant,
                        (int) $row['stock'],
                        'received_shipment',
                        $actor,
                        'Initial stock from product CSV import.',
                    );
                }

                $this->audit->record('products.import', $product, null, [
                    'line' => $row['line'] ?? null,
                    'stock' => (int) ($row['stock'] ?? 0),
                ]);

                $imported++;
            }

            return $imported;
        });
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = str_replace([',', ' ', 'Rs.', 'PKR'], '', (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function integer(mixed $value): ?int
    {
        $number = $this->number($value);

        return $number === null ? null : (int) round($number);
    }

    private function nullableString(mixed $value, ?int $limit = null): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        return $limit ? mb_substr($value, 0, $limit) : $value;
    }
}
