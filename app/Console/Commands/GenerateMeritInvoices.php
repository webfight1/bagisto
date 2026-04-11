<?php

namespace App\Console\Commands;

use App\Listeners\CreateMeritInvoice;
use Illuminate\Console\Command;
use Webkul\Sales\Models\Order;

class GenerateMeritInvoices extends Command
{
    protected $signature = 'merit:generate-invoices 
                            {--order-id= : Generate invoice for specific order ID}
                            {--from-date= : Generate invoices for orders from this date (Y-m-d)}
                            {--status=processing : Order status filter (processing, completed, or all)}
                            {--retry-failed : Retry failed invoices}';

    protected $description = 'Generate Merit invoices for orders';

    public function handle(CreateMeritInvoice $listener): int
    {
        $orderId = $this->option('order-id');
        $fromDate = $this->option('from-date');
        $status = $this->option('status');
        $retryFailed = $this->option('retry-failed');

        // Build query
        $query = Order::query();

        if ($orderId) {
            $query->where('id', $orderId);
        } elseif ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('id', 'desc')->get();

        if ($orders->isEmpty()) {
            $this->error('No orders found matching criteria.');
            return Command::FAILURE;
        }

        $this->info("Found {$orders->count()} order(s) to process.");

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        $success = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                // If retry-failed flag is set, delete failed invoice records to allow retry
                if ($retryFailed) {
                    \App\Models\MeritInvoice::where('order_id', $order->id)
                        ->where('status', 'failed')
                        ->delete();
                }

                $listener->handle($order);
                
                // Check if invoice was created
                $invoice = \App\Models\MeritInvoice::where('order_id', $order->id)
                    ->where('status', 'created')
                    ->first();
                
                if ($invoice) {
                    $success++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Order #{$order->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Results:");
        $this->info("✓ Success: {$success}");
        $this->info("⊘ Skipped: {$skipped}");
        $this->info("✗ Failed: {$failed}");

        return Command::SUCCESS;
    }
}
