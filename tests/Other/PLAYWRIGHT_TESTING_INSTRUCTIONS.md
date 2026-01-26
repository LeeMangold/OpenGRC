# OpenGRC Comprehensive Playwright MCP Testing

Use playwright-mcp

**Base URL:** `https://opengrc.test`
IGNORE BROWSER SSL ERRORS

Do not attempt to fix any errors. Only bug finding and reporting

---

## Bug Detection Rules

Flag as **BUG** if you see:
- **Repeated/duplicate content** - Same value shown twice (e.g., "Security, Security")
- **Raw HTML tags visible** - `<p>`, `<div>`, `&nbsp;`, `<strong>`, etc. as text
- **HTML in modal titles** - Modal headings showing markup
- **Console errors** - Use `browser_console_messages` level:"error"
- **404/500 errors**
- **Missing translations** - Text like `navigation.groups.xxx`
- **Forms that fail to submit**
- **Buttons that do nothing**
- **Modals that won't open/close**
- **Empty dropdowns** that should have data
- **Broken links**
- **Actions that don't complete** (no success notification)
- **Data that doesn't save**
- **Filters that don't filter**
- **Search that doesn't work**
- **Pagination issues**

**Report format:** `[BUG] Page | Element | Type | Description`

---

## Multi-Agent Strategy

### Phase 1: Main Agent - Setup
```
1. Login
2. Create base test records
```

### Phase 2: Parallel Agents (spawn all simultaneously)
```
Agent 1: Programs, Standards, Controls (List + Create + View + Edit)
Agent 2: Implementations, Risks, Policies (List + Create + View + Edit)
Agent 3: Audits, Vendors, Applications, Assets (List + Create + View + Edit)
Agent 4: Admin Panel (All pages and settings)
Agent 5: Special Pages (Dashboard, Import, Trust Center, Surveys)
```

### Phase 3: Main Agent - Consolidate
```
Collect all bug reports, run console check, compile report
```

---

## Login Procedure (All Agents)

```
1. browser_navigate: https://opengrc.test/app/login
2. browser_snapshot
3. Fill email field
4. Fill password field
5. Click login button
6. Verify dashboard loads
```

---

## Page Interaction Checklist

### For Every LIST PAGE, test:

```
TABLE:
[ ] Table loads with data (or empty state message)
[ ] Click column headers to sort (test 2-3 columns)
[ ] Click sort again to reverse
[ ] Use search box - type a term, verify results filter
[ ] Clear search, verify all results return

FILTERS:
[ ] Click filter button/icon
[ ] Open each filter dropdown
[ ] Select a filter value
[ ] Verify table filters
[ ] Clear/reset filters
[ ] Test multiple filters combined

PAGINATION:
[ ] If multiple pages, click page 2
[ ] Click "next" arrow
[ ] Click "previous" arrow
[ ] Change per-page count if available

COLUMNS:
[ ] Click column toggle/visibility button
[ ] Hide a column
[ ] Show it again
[ ] Verify column order

HEADER ACTIONS:
[ ] Click "Create/New" button - verify form opens
[ ] Click "Export" button - verify export modal/download
[ ] Test any other header buttons

ROW ACTIONS:
[ ] Click View on a row - verify view page/modal opens
[ ] Click Edit on a row - verify edit form opens
[ ] Click any dropdown menu on rows - verify options appear
[ ] Test "Delete" if safe (or just verify modal appears, then cancel)

BULK ACTIONS:
[ ] Select multiple rows via checkboxes
[ ] Click bulk action dropdown
[ ] Verify options appear (Export Selected, Delete, etc.)
[ ] Test "Export Selected"
[ ] Deselect all
```

### For Every CREATE PAGE, test:

```
FORM FIELDS - interact with EVERY field:
[ ] Text inputs - type values
[ ] Textareas - type multi-line text
[ ] Select dropdowns - open, select value
[ ] Multi-select - select multiple values
[ ] Date pickers - open calendar, select date
[ ] Rich text editors - type text, try bold/italic buttons
[ ] File uploads - if present, note it (don't actually upload)
[ ] Toggles/switches - click to toggle
[ ] Radio buttons - select different options
[ ] Checkboxes - check/uncheck

VALIDATION:
[ ] Submit empty form - verify validation errors appear
[ ] Check required field indicators (asterisks)
[ ] Fill required fields only, submit
[ ] Verify success notification
[ ] Verify redirect to view/list page

CANCEL:
[ ] Start filling form, click Cancel/Back
[ ] Verify no record created
```

### For Every VIEW PAGE, test:

```
INFO DISPLAY:
[ ] All fields display values (not empty unexpectedly)
[ ] No duplicate values (e.g., "Security, Security")
[ ] No raw HTML tags visible
[ ] Rich text renders properly (bold, lists, etc.)
[ ] Dates formatted correctly
[ ] Badges/status show correctly

HEADER ACTIONS:
[ ] Click Edit button - verify edit page opens
[ ] Click Delete button - verify confirmation modal (then cancel)
[ ] Test any other action buttons (Export PDF, etc.)

RELATION MANAGER TABS:
For EACH tab on the page:
[ ] Click tab - verify it loads
[ ] Check table displays data
[ ] Click View on a row - check modal title for HTML, check content
[ ] Click Edit on a row if available - check modal works
[ ] Click Create/Add button - check form modal opens
[ ] Close modals properly
[ ] Test Attach/Detach if available

WIDGETS:
[ ] Any stat widgets display numbers
[ ] Any charts render
```

### For Every EDIT PAGE, test:

```
[ ] Form pre-fills with existing data
[ ] Modify a text field
[ ] Modify a select field
[ ] Save changes
[ ] Verify success notification
[ ] Verify changes persisted (view page shows new values)
[ ] Test Cancel - changes should not save
```

---

## Agent 1: Programs, Standards, Controls

### Programs

**List Page: /app/programs**
```
1. Navigate to /app/programs
2. Run LIST PAGE checklist above
3. Note any bugs
```

**Create Page: /app/programs/create**
```
Fields to fill:
- name (text, required)
- description (rich text)
- program_manager_id (select, required)
- scope_status (select, required)
- department (select)
- scope (select)

Run CREATE PAGE checklist
```

**View Page: /app/programs/[id]**
```
Check fields: Name, Description, Program Manager, Scope Status, Department, Scope

Relation Manager Tabs:
- Standards tab - test View modal
- Controls tab - test View modal
- Risks tab - test View modal
- Audits tab - test View modal

Run VIEW PAGE checklist
```

**Edit Page: /app/programs/[id]/edit**
```
Run EDIT PAGE checklist
```

### Standards

**List Page: /app/standards**
```
Run LIST PAGE checklist

Special actions to test:
- "Set In Scope" action on rows
- "Set Out of Scope" action on rows
- Restore action (if trashed items)
```

**Create Page: /app/standards/create**
```
Fields:
- name (text, required)
- code (text, required, unique)
- authority (text, required)
- status (select, required)
- department (select)
- scope (select)
- reference_url (url)
- description (rich text, required)

Run CREATE PAGE checklist
```

**View Page: /app/standards/[id]**
```
Check fields: Name, Code, Authority, Status, Department, Scope, Description

Relation Manager Tabs:
- Controls tab - test View modal, check for HTML in title
- Audits tab - test View modal

Run VIEW PAGE checklist
```

**Edit Page: /app/standards/[id]/edit**
```
Run EDIT PAGE checklist
```

### Controls

**List Page: /app/controls**
```
Run LIST PAGE checklist

Filters to test:
- Standard filter
- Effectiveness filter
- Type filter
- Category filter
- Enforcement filter
- Applicability filter
- Owner filter
- Department filter
- Scope filter
```

**Create Page: /app/controls/create**
```
Fields:
- code (text, required, unique)
- standard_id (select, required)
- enforcement (select, required)
- type (select, required)
- category (select, required)
- control_owner_id (select)
- department (select)
- scope (select)
- title (text, required)
- description (rich text, required)
- discussion (rich text)
- test (rich text)

Run CREATE PAGE checklist
```

**View Page: /app/controls/[id]**
```
Check fields: Code, Title, Type, Category, Enforcement, Effectiveness,
              Applicability, Department, Scope, Description, Discussion, Test

Relation Manager Tabs:
- Implementations tab - CRITICAL: test View modal, check title for HTML
- Audit Items tab - test View modal
- Policies tab - test View modal

Run VIEW PAGE checklist
```

**Edit Page: /app/controls/[id]/edit**
```
Run EDIT PAGE checklist
```

---

## Agent 2: Implementations, Risks, Policies

### Implementations

**List Page: /app/implementations**
```
Run LIST PAGE checklist

Filters: status, effectiveness, owner, department, scope
```

**Create Page: /app/implementations/create**
```
Fields:
- code (text, required, unique)
- status (select, required)
- controls (multi-select)
- applications (multi-select)
- vendors (multi-select)
- title (text, required)
- implementation_owner_id (select)
- department (select)
- scope (select)
- details (rich text, required)
- test_procedure (rich text)
- notes (rich text)

Run CREATE PAGE checklist
```

**View Page: /app/implementations/[id]**
```
Check fields: Code, Title, Status, Effectiveness, Department, Scope,
              Details, Test Procedure, Notes

Relation Manager Tabs:
- Controls tab
- Audit Items tab
- Risks tab
- Assets tab
- Applications tab
- Vendors tab
- Policies tab

Test View modal on EACH tab
Run VIEW PAGE checklist
```

**Edit Page**
```
Run EDIT PAGE checklist
```

### Risks

**List Page: /app/risks**
```
Run LIST PAGE checklist

Special: Check risk matrix colors display
Filters: inherent_likelihood, inherent_impact, residual_likelihood,
         residual_impact, department, scope, is_active
Test "Reset Filters" button
```

**Create Page: /app/risks/create**
```
Fields:
- code (text, required, unique)
- name (text, required)
- description (textarea)
- inherent_likelihood (toggle buttons, required)
- inherent_impact (toggle buttons, required)
- residual_likelihood (toggle buttons, required)
- residual_impact (toggle buttons, required)
- implementations (multi-select)
- status (select, required)
- is_active (toggle)
- department (select)
- scope (select)

Run CREATE PAGE checklist
Test toggle buttons specifically
```

**View Page: /app/risks/[id]**
```
Check: Risk scores calculate correctly
Check: Color coding on risk levels

Relation Manager Tabs:
- Implementations tab
- Policies tab
- Mitigations tab

Run VIEW PAGE checklist
```

### Policies

**List Page: /app/policies**
```
Run LIST PAGE checklist
```

**Create Page: /app/policies/create**
```
Document all fields present
Run CREATE PAGE checklist
```

**View Page: /app/policies/[id]**
```
Check "Details" page link if exists
Relation Manager Tabs:
- Implementations tab
- Risks tab
- Exceptions tab
- Controls tab

Run VIEW PAGE checklist
```

---

## Agent 3: Audits, Vendors, Applications, Assets

### Audits

**List Page: /app/audits**
```
Run LIST PAGE checklist
Filters: manager_id, status, department, scope
```

**Create Page (WIZARD): /app/audits/create**
```
STEP 1 - Audit Type:
- Read both audit type descriptions
- Select "Standards Audit"
- Select a standard from dropdown
- Click Next
- Go back, select "Implementations Audit"
- Click Next
- Go back, select "Program Audit"
- Select a program
- Click Next

STEP 2 - Basic Information:
- title (text, required)
- manager_id (select, required)
- description (textarea)
- start_date (date picker, required)
- end_date (date picker, required)
- department (select)
- scope (select)
- Click Next

STEP 3 - Audit Details:
- Test the two-sided multi-select component
- Use search to filter items
- Select individual items
- Use "Random Select" dropdown - Random 5
- Use "Random (Unassessed)" dropdown
- Use "Oldest Assessed" dropdown
- Select All / Deselect All
- Click Create

Verify audit created with audit items
```

**View Page: /app/audits/[id]**
```
Check: Stats widget displays correctly
Check: Status badge

Relation Manager Tabs:
- Audit Items tab - test View modal
- Data Requests tab - test View modal, Create
- Attachments tab - test upload UI

Header actions:
- Export/PDF actions if present
- Complete Audit action if available

Run VIEW PAGE checklist
```

**Import IRL Page: /app/audits/import-irl/[id]**
```
If accessible, test the IRL import interface
```

### Vendors

**List Page: /app/vendors** (or via Vendor Manager)
```
Run LIST PAGE checklist
Filters: status, risk_rating, vendor_manager_id

Row Action: "Send Survey" - open modal, check form fields, cancel
```

**Create Page: /app/vendors/create**
```
Fields (in sections):
Vendor Information:
- name (text, required)
- url (url)
- description (textarea)

Status & Risk:
- status (select, required)
- risk_rating (select, required)

Management:
- vendor_manager_id (select, required)

Vendor Contact:
- contact_name (text)
- contact_email (email)
- contact_phone (tel)
- address (textarea)

Additional Information (collapsible):
- notes (textarea)
- logo (file upload)

Run CREATE PAGE checklist
Test collapsible section
```

**View Page: /app/vendors/[id]**
```
Check infolist displays all fields
Check: risk_score displays correctly

Relation Manager Tabs:
- Applications tab
- Implementations tab
- Surveys tab
- Vendor Users tab - test creating vendor user
- Vendor Documents tab

Run VIEW PAGE checklist
```

### Applications

**List Page: /app/applications**
```
Run LIST PAGE checklist
```

**Create/View/Edit Pages**
```
Document all fields
Run all checklists
```

### Assets

**List Page: /app/assets**
```
Run LIST PAGE checklist
```

**Create/View/Edit Pages**
```
Document all fields
Run all checklists
```

---

## Agent 4: Admin Panel

**Navigate to: /admin**

### Users: /admin/users
```
List page checklist
Create user:
- name, email, password fields
- role assignment
View user page
Edit user
```

### Roles: /admin/roles
```
List page checklist
Create role if allowed
Edit role permissions
```

### Role Permission Matrix: /admin/role-permission-matrix
```
View matrix
Toggle permissions if interactive
```

### Taxonomies: /admin/taxonomies
```
List page checklist
Create taxonomy term
Edit term
```

### Bundles: /admin/bundles
```
View bundles list
View bundle details
```

### Settings Pages - Test EACH ONE:

**/admin/settings**
```
Check all settings display
Modify a setting
Save
Verify saved
```

**/admin/security-settings**
```
Document all security options
Test toggles/inputs
Save
```

**/admin/mail-settings**
```
Mail configuration options
Test connection button if exists
```

**/admin/mail-template-settings**
```
View templates
Edit a template if possible
```

**/admin/ai-settings**
```
AI configuration options
```

**/admin/storage-settings**
```
Storage driver options
```

**/admin/authentication-settings**
```
Auth providers
OAuth settings
```

**/admin/report-settings**
```
Report configuration
```

**/admin/trust-center-settings**
```
Trust center options
```

**/admin/vendor-portal-settings**
```
Vendor portal configuration
```

### Other Admin Pages:

**/admin/about**
```
System info displays
```

**/admin/activity-log**
```
Log entries display
Filtering works
```

**/admin/queue-monitor**
```
Queue status displays
```

**/admin/api-documentation**
```
API docs load (Scramble)
Endpoints listed
```

---

## Agent 5: Special Pages

### Dashboard: /app/dashboard
```
All widgets load
Stat numbers display
Charts render (if any)
Click on widgets that are links
```

### Import: /app/import
```
Import wizard loads
Framework options display
Test first step of import (don't complete)
```

### Vendor Manager: /app/vendor-manager
```
Page loads
Vendor list/grid displays
Actions work
```

### Trust Center Manager: /app/trust-center-manager
```
Page loads
Configuration options work
```

### Trust Center Content Blocks: /app/trust-center-content-blocks
```
Run LIST PAGE checklist
Create content block
View/Edit content block
```

### Trust Center Documents: /app/trust-center-documents
```
Run LIST PAGE checklist
Create document
View/Edit document
```

### Trust Center Access Requests: /app/trust-center-access-requests
```
View requests list
View individual request
Approve/Deny actions
```

### Public Trust Center: /trust
```
Page loads (may not need login)
Content displays
Request access form works
```

### Surveys: /app/surveys
```
Run LIST PAGE checklist
View survey
Respond to survey page
Score survey page
```

### Survey Templates: /app/survey-templates
```
Run LIST PAGE checklist
Create template
Edit template questions
View template
```

### To-Do Page: /app/to-do
```
Page loads
Tasks display
```

### Data Requests: /app/data-requests
```
Run LIST PAGE checklist
Create request
View request
```

### Data Request Responses: /app/data-request-responses
```
Run LIST PAGE checklist
Create response
View response
```

### File Attachments: /app/file-attachments
```
Run LIST PAGE checklist
Upload interface
```

### Certifications: /app/certifications
```
Run LIST PAGE checklist
Create/View/Edit
```

### Permissions: /app/permissions
```
List permissions
Create if allowed
Edit
```

---

## Output Format

```
# Comprehensive Test Results

## Agent [X] - [Area]

### [Resource/Page Name]

#### List Page
- Bugs: [list or "None"]
- Notes: [observations]

#### Create Page
- Fields tested: [list]
- Bugs: [list or "None"]

#### View Page
- Relation tabs tested: [list]
- Modal issues: [list or "None"]
- Bugs: [list or "None"]

#### Edit Page
- Bugs: [list or "None"]

---

## Summary
- Pages tested: X
- Forms tested: X
- Modals tested: X
- Total bugs: X

## All Bugs
1. [BUG] Page | Element | Type | Description
2. ...

## Console Errors
[list]
```

---

## Final Checklist

- [ ] Every list page: sorting, filtering, search, pagination tested
- [ ] Every create form: all fields filled, validation tested
- [ ] Every view page: all sections checked, all relation tabs opened
- [ ] Every relation manager: View modal tested, title checked for HTML
- [ ] Every edit form: changes saved and verified
- [ ] All admin settings pages visited and tested
- [ ] Dashboard widgets verified
- [ ] Import page tested
- [ ] Console errors collected
