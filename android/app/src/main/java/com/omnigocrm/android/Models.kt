package com.omnigocrm.android

data class EntityItem(val id: String, val title: String, val subtitle: String = "")
data class LeadItem(
    val id: String,
    val firstName: String,
    val lastName: String,
    val company: String,
    val mobile: String,
    val whatsapp: String,
    val status: String,
    val stage: String,
)
