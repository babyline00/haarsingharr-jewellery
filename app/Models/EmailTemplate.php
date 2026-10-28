<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'name', 'subject', 'body', 'variables', 'is_active'];

    protected function casts(): array
    {
        return ['variables' => 'array', 'is_active' => 'boolean'];
    }

    /**
     * Render the template body/subject with the given data. Placeholders
     * use double-curly syntax, e.g. "Hi {{customer_name}} — order {{order_number}}".
     */
    public function render(array $data = []): array
    {
        $replacements = [];
        foreach (($this->variables ?? []) as $variable) {
            $name = is_array($variable) ? ($variable['name'] ?? '') : (string) $variable;
            if ($name === '') {
                continue;
            }
            $replacements["{{".$name."}}"] = (string) ($data[$name] ?? "{{{$name}}}");
        }

        return [
            'subject' => strtr($this->subject, $replacements),
            'body'    => strtr($this->body, $replacements),
        ];
    }

    /** Sample values for the variables palette (used by preview & test sends). */
    public static function sampleData(array $variables, array $base = []): array
    {
        $defaults = [
            'customer_name'    => 'Fatima Khan',
            'order_number'     => 'HS-26-000123',
            'order_status'     => 'Delivered',
            'order_total'      => 'Rs. 12,500',
            'product_name'     => 'Gold Heritage Pendant',
            'support_email'    => 'care@haarsingharr.pk',
            'store_name'       => 'Haarsingharr.pk',
            'tracking_number'  => 'TCS-99182',
            'refund_amount'    => 'Rs. 4,200',
            'reply'            => 'Thank you for reaching out to us.',
        ];

        $sample = [];
        foreach ($variables as $variable) {
            $name = is_array($variable) ? ($variable['name'] ?? '') : (string) $variable;
            if ($name !== '') {
                $sample[$name] = $defaults[$name]
                    ?? ($base[$name] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $name)));
            }
        }

        return $sample;
    }
}
