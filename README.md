# FluentCRM Subscriptions for JetFormBuilder
This add-on adds new action types to JetFormBuilder, allowing you to create subscription forms and manage contacts for FluentCRM.

Stable tag: 1.2

This is an add-on for the **JetFormBuilder** and **FluentCRM** plugins.

**Website:** [https://jetformbuilder.com/](https://jetformbuilder.com/)  
**FluentCRM:** [https://fluentcrm.com/](https://fluentcrm.com/)

---

### ℹ️ Requirements
To use this plugin, make sure you have both **JetFormBuilder** and **FluentCRM** (free version) installed and active on your site.

---

### ⚙️ Installation
1. Install the plugin like any normal WordPress plugin, then activate it.  
2. Open an existing **JetForm** or create a new one.  
3. In the **Post Submit Actions** section, add a new action and select one of the available actions below.

---

## Actions

### 📝 FluentCRM Subscribe
Full subscription management — create new contacts or update existing ones.

**Fields Map:**  
- Email *(required)*  
- First Name *(optional)*  
- Last Name *(optional)*  
- Phone Number *(optional)*

You can specify one or more **CRM lists** and **tags**.

**Options:**

**Bypass double opt-in**  
If enabled, the user will *not* receive a confirmation email and will be added as a subscriber immediately.  
If disabled, FluentCRM will send the confirmation email and follow the usual opt-in process.

**Add Only**  
If enabled, the form will only handle *new* subscriptions. If the same person subscribes again with the same email, you can notify them that they are already subscribed (and the form won't trigger the success action).  
If disabled, existing subscribers' data can be updated.

---

### 🏷️ FluentCRM Add List & Tags
Lightweight action for assigning lists and tags to contacts — without updating their profile data.

**Fields Map (only used when creating a new contact):**  
- Email *(required)*  
- First Name *(optional)*  
- Last Name *(optional)*  
- Phone Number *(optional)*

You can specify one or more **CRM lists** and **tags** (displayed side by side).

**Options:**

**Use current user**  
Identifies the contact by the logged-in user's email instead of a form field. Enable this when the form is restricted to authenticated users.

**Skip if not in CRM**  
When enabled, contacts not yet in FluentCRM are silently skipped — no lists or tags are assigned and no new contact is created.

**New contact status**  
Status assigned to contacts that are automatically created because they were not found in FluentCRM (e.g. *subscribed*, *pending*, etc.). Only relevant when "Skip if not in CRM" is disabled.

> **Note:** Existing contacts are never updated by this action — only lists and tags are assigned. The Fields Map is only used when a brand new contact needs to be created.
