<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;

class DiagnoseStoreProduct extends Command
{
    protected $signature = 'stores:diagnose-product
                            {store : Store slug from the public URL}
                            {productId : Numeric product ID from the request}';

    protected $description = 'Read-only diagnosis of a product visibility within a public store context.';

    public function handle(): int
    {
        $storeSlug = (string) $this->argument('store');
        $productId = (string) $this->argument('productId');

        if (!ctype_digit($productId) || (int) $productId < 1) {
            $this->error('The productId argument must be a positive integer.');

            return self::INVALID;
        }

        $merchant = User::query()->where('store_slug', $storeSlug)->first(['id', 'store_slug']);

        if ($merchant === null) {
            $this->error("Store '{$storeSlug}' was not found.");

            return self::FAILURE;
        }

        $product = Product::withoutGlobalScopes()
            ->withTrashed()
            ->find((int) $productId, ['id', 'merchant_id', 'is_active', 'deleted_at']);

        if ($product === null) {
            $this->table(
                ['Store slug', 'Store merchant ID', 'Product ID', 'Result'],
                [[$storeSlug, $merchant->id, $productId, 'product_not_found']],
            );
            $this->warn('The product does not exist in the database. Use an ID returned by this store\'s /products endpoint.');

            return self::FAILURE;
        }

        $merchantMatches = (int) $product->merchant_id === (int) $merchant->id;
        $isTrashed = $product->trashed();
        $isVisible = $merchantMatches && (bool) $product->is_active && !$isTrashed;

        $this->table(
            ['Store slug', 'Expected merchant ID', 'Product ID', 'Product merchant ID', 'Active', 'Soft deleted', 'Publicly visible'],
            [[
                $storeSlug,
                $merchant->id,
                $product->id,
                $product->merchant_id,
                (bool) $product->is_active ? 'yes' : 'no',
                $isTrashed ? 'yes' : 'no',
                $isVisible ? 'yes' : 'no',
            ]],
        );

        if (!$merchantMatches) {
            $this->warn('Reason: merchant_mismatch. The product belongs to another merchant and must remain inaccessible from this store URL.');

            return self::FAILURE;
        }

        if ($isTrashed) {
            $this->warn('Reason: product_soft_deleted. Restore the product from the merchant dashboard if it should be publicly available.');

            return self::FAILURE;
        }

        if (!(bool) $product->is_active) {
            $this->warn('Reason: product_inactive. Activate the product from the merchant dashboard if it should be publicly available.');

            return self::FAILURE;
        }

        $this->info('The product is visible in this store. If the request still returns 404, clear application caches and inspect the latest Laravel log context.');

        return self::SUCCESS;
    }
}
