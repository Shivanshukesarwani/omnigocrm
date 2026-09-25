<?php
namespace Database\Seeders;
use App\Models\MessageTemplate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin=User::create(['name'=>'CRM Admin','email'=>'admin@shivanshu.local','phone'=>'','role'=>'admin','password'=>Hash::make('ChangeMe123!')]);
        User::create(['name'=>'Sales Executive','email'=>'sales@shivanshu.local','phone'=>'','role'=>'sales','password'=>Hash::make('ChangeMe123!')]);
        $templates=[
            ['name'=>'Welcome Message','situation'=>'welcome','body'=>'Hi {first_name}, thank you for contacting {company_name}. We received your requirement{requirement} and would be happy to discuss it with you. Regards, {salesperson}.'],
            ['name'=>'First Follow-up','situation'=>'follow_up','body'=>'Hi {first_name}, just following up regarding your requirement. Please let me know a convenient time to discuss it further. Regards, {salesperson}.'],
            ['name'=>'No Response','situation'=>'not_received','body'=>'Hi {first_name}, I tried reaching you regarding your requirement. Whenever convenient, please reply here and I will assist you. Regards, {salesperson}.'],
            ['name'=>'Urgent Meeting','situation'=>'urgent_meeting','body'=>'Hi {first_name}, we need to discuss your requirement urgently. Please let me know your earliest convenient time for a quick meeting. Regards, {salesperson}.'],
            ['name'=>'Further Discussion','situation'=>'further_discuss','body'=>'Hi {first_name}, can we schedule a short meeting to discuss the next steps for your requirement? Regards, {salesperson}.'],
            ['name'=>'Sales Message','situation'=>'sales','body'=>'Hi {first_name}, I wanted to share an option that may fit your requirement. Please let me know if you would like the details or a quotation. Regards, {salesperson}.'],
            ['name'=>'Marketing Message','situation'=>'marketing','body'=>'Hi {first_name}, sharing a quick update from {company_name}. We have new services and offers that may be useful for your business. Reply here for details.'],
            ['name'=>'Payment Reminder','situation'=>'payment_reminder','body'=>'Hi {first_name}, this is a gentle reminder regarding the pending payment. Please let us know if you need the invoice or payment details again. Regards, {salesperson}.'],
        ];
        foreach($templates as $t) MessageTemplate::create($t);
        foreach([['name'=>'Graphic Design','price'=>0,'unit'=>'project'],['name'=>'Flex Printing','price'=>0,'unit'=>'sq ft'],['name'=>'Visiting Card Printing','price'=>0,'unit'=>'box'],['name'=>'Website Development','price'=>0,'unit'=>'project']] as $p) Product::create($p);
    }
}
