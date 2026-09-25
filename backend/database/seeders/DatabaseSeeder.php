<?php
namespace Database\Seeders;
use App\Models\Workspace;
use App\Models\MessageTemplate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder {
 public function run(): void {
  $workspace=Workspace::firstOrCreate(['slug'=>'default'],['name'=>env('CRM_COMPANY_NAME','OmniGoCRM'),'plan'=>'trial','status'=>'active']);
  $admin=User::firstOrCreate(['email'=>env('CRM_ADMIN_EMAIL','admin@example.com')],['workspace_id'=>$workspace->id,'name'=>'CRM Admin','phone'=>env('CRM_ADMIN_PHONE',''),'role'=>'super_admin','password'=>Hash::make(env('CRM_ADMIN_PASSWORD','CHANGE_ME_AFTER_INSTALL'))]);
  User::firstOrCreate(['email'=>env('CRM_SALES_EMAIL','sales@example.com')],['workspace_id'=>$workspace->id,'name'=>'Sales Executive','phone'=>'','role'=>'sales','password'=>Hash::make(env('CRM_SALES_PASSWORD','CHANGE_ME_AFTER_INSTALL'))]);
  $templates=[
   ['name'=>'Welcome Message','situation'=>'welcome','body'=>'Hi {first_name}, thank you for contacting {company_name}. We received your requirement and would be happy to discuss it with you. Regards, {salesperson}.'],
   ['name'=>'First Follow-up','situation'=>'follow_up','body'=>'Hi {first_name}, just following up regarding your requirement. Please let me know a convenient time to discuss it further. Regards, {salesperson}.'],
   ['name'=>'No Response','situation'=>'not_received','body'=>'Hi {first_name}, I tried reaching you regarding your requirement. Whenever convenient, please reply here and I will assist you. Regards, {salesperson}.'],
   ['name'=>'Urgent Meeting','situation'=>'urgent_meeting','body'=>'Hi {first_name}, please let me know your earliest convenient time for a quick meeting. Regards, {salesperson}.'],
   ['name'=>'Further Discussion','situation'=>'further_discuss','body'=>'Hi {first_name}, can we schedule a short meeting to discuss the next steps? Regards, {salesperson}.'],
   ['name'=>'Sales Message','situation'=>'sales','body'=>'Hi {first_name}, I wanted to share an option that may fit your requirement. Reply here for details or a quotation. Regards, {salesperson}.'],
   ['name'=>'Marketing Message','situation'=>'marketing','body'=>'Hi {first_name}, sharing an update from {company_name}. Reply here for details.'],
   ['name'=>'Quotation Follow-up','situation'=>'quotation','body'=>'Hi {first_name}, following up on the quotation. Please let us know if you need any changes. Regards, {salesperson}.'],
   ['name'=>'Payment Reminder','situation'=>'payment_reminder','body'=>'Hi {first_name}, this is a gentle reminder regarding the pending payment. Regards, {salesperson}.'],
   ['name'=>'Thank You','situation'=>'thank_you','body'=>'Thank you {first_name} for choosing {company_name}. We appreciate your business.'],
  ];
  foreach($templates as $t) MessageTemplate::firstOrCreate(['workspace_id'=>$workspace->id,'name'=>$t['name']],$t+['active'=>true]);
  Product::firstOrCreate(['workspace_id'=>$workspace->id,'name'=>'Graphic Design Service'],['price'=>0,'unit'=>'project','active'=>true]);
  Product::firstOrCreate(['workspace_id'=>$workspace->id,'name'=>'Printing Service'],['price'=>0,'unit'=>'project','active'=>true]);
 }
}
