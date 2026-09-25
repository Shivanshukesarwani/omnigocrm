package com.omnigocrm
data class LeadItem(val id:Long,val firstName:String,val lastName:String,val company:String,val mobile:String,val status:String)
data class ContactItem(val id:Long,val firstName:String,val lastName:String,val company:String,val mobile:String,val customerCode:String?)
data class CustomerItem(val id:Long,val code:String,val name:String,val company:String,val mobile:String)
data class CompanyItem(val id:Long,val name:String,val phone:String,val email:String)
data class TaskItem(val id:Long,val title:String,val dueAt:String,val priority:String,val status:String)
