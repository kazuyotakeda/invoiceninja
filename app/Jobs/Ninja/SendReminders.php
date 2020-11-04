<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Jobs\Ninja;

use App\DataMapper\InvoiceItem;
use App\Events\Invoice\InvoiceWasEmailed;
use App\Jobs\Entity\EmailEntity;
use App\Libraries\MultiDB;
use App\Models\Invoice;
use App\Utils\Ninja;
use App\Utils\Traits\MakesDates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, MakesDates;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        info("Sending reminders ".Carbon::now()->format('Y-m-d h:i:s'));

        if (! config('ninja.db.multi_db_enabled')) {

            $this->sendReminderEmails();


        } else {
            //multiDB environment, need to
            foreach (MultiDB::$dbs as $db) 
            {

                MultiDB::setDB($db);

                $this->sendReminderEmails();
            }

        }

    }


    private function chargeLateFee()
    {

    }

    private function sendReminderEmails()
    {
        $invoices = Invoice::where('is_deleted', 0)
                           ->where('balance', '>', 0)
                           ->whereDate('next_send_date', '<=', now()->startOfDay())
                           ->whereNotNull('next_send_date')
                           ->cursor();

        //we only need invoices that are payable
        $invoices->filter(function ($invoice){

            return $invoice->isPayable();

        })->each(function ($invoice){

                $reminder_template = $invoice->calculateTemplate('invoice');

                if(in_array($reminder_template, ['reminder1', 'reminder2', 'reminder3', 'endless_reminder']))
                    $this->sendReminder($invoice, $reminder_template);

        });

    }

    private function checkSendSetting($invoice, $template)
    {
        switch ($template) {
            case 'reminder1':
                return $invoice->client->getSetting('enable_reminder1');
                break;
            case 'reminder2':
                return $invoice->client->getSetting('enable_reminder2');
                break;
            case 'reminder3':
                return $invoice->client->getSetting('enable_reminder3');
                break;
            case 'endless_reminder':
                return $invoice->client->getSetting('enable_reminder_endless');
                break;            
            default:
                return false;
                break;
        }
    }

    private function calculateNextSendDate($invoice, $template)
    {
        
    }

    private function sendReminder($invoice, $template)
    {
        $invoice = $this->calcLateFee($invoice, $template);

        $invoice->invitations->each(function ($invitation) use($template, $invoice){

            //only send if enable_reminder setting is toggled to yes
            if($this->checkSendSetting($invoice, $template))
                EmailEntity::dispatch($invitation, $invitation->company, $template);

        });

            if ($invoice->invitations->count() > 0)
                event(new InvoiceWasEmailed($invoice->invitations->first(), $invoice->company, Ninja::eventVars()));

            $invoice->last_sent_date = now();
            $invoice->reminder_last_send = now();
            $invoice->next_send_date = $this->calculateNextSendDate($invoice, $template);

            if(in_array($template, ['reminder1', 'reminder2', 'reminder3']))
                $invoice->{$template."_send"} = now();

            //calculate next_send_date

            $invoice->save();
    }

    private function calcLateFee($invoice, $template) :Invoice
    {
        $late_fee_amount = 0;
        $late_fee_percent = 0;

        switch ($template) {
            case 'reminder1':
                $late_fee_amount = $invoice->client->getSetting('late_fee_amount1');
                $late_fee_percent = $invoice->client->getSetting('late_fee_percent1');
                break;
            case 'reminder2':
                $late_fee_amount = $invoice->client->getSetting('late_fee_amount2');
                $late_fee_percent = $invoice->client->getSetting('late_fee_percent2');
                break;
            case 'reminder3':
                $late_fee_amount = $invoice->client->getSetting('late_fee_amount3');
                $late_fee_percent = $invoice->client->getSetting('late_fee_percent3');
                break;
            case 'endless_reminder':
                $late_fee_amount = $invoice->client->getSetting('late_fee_endless_amount');
                $late_fee_percent = $invoice->client->getSetting('late_fee_endless_percent');
                break;            
            default:
                $late_fee_amount = 0;
                $late_fee_percent = 0;
                break;
        }

        return $this->setLateFee($invoice, $late_fee_amount, $late_fee_percent);

    }


    private function setLateFee($invoice, $amount, $percent) :Invoice
    {
        if ($amount <= 0 && $percent <= 0) {
            return $invoice;
        }

        $fee = $amount;

        if ($invoice->partial > 0) 
            $fee += round($invoice->partial * $percent / 100, 2);
        else
            $fee += round($invoice->balance * $percent / 100, 2);

        $invoice_item = new InvoiceItem;
        $invoice_item->type_id = '5';
        $invoice_item->product_key = trans('texts.fee');
        $invoice_item->notes = ctrans('texts.late_fee_added', ['date' => $this->formatDate(now()->startOfDay(), $invoice->client->date_format())]);
        $invoice_item->quantity = 1;
        $invoice_item->cost = $fee;

        $invoice_items = $invoice->line_items;
        $invoice_items[] = $invoice_item;

        $invoice->line_items = $invoice_items;

        /**Refresh Invoice values*/
        $invoice = $invoice->calc()->getInvoice();

        return $invoice;

    }

}