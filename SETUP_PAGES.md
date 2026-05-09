# WordPress Page Setup — SJIOC Theme

## 1. Permalink Settings

1. Go to **wp-admin → Settings → Permalinks**
2. Select **Post name** (`/%postname%/`)
3. Click **Save Changes**

---

## 2. Reading Settings (Homepage)

1. Go to **wp-admin → Settings → Reading**
2. Set **"Your homepage displays"** to **A static page**
3. Create a blank page titled **Home** first (see step 3), then return here and assign it
4. Leave **Posts page** blank (no blog)
5. Click **Save Changes**

---

## 3. Create Pages

Go to **wp-admin → Pages → Add New** for each page below.

| Title | Slug | Template |
|---|---|---|
| Home | `home` | Home Page |
| About Us | `about-us` | About Us Page |
| Worship & Services | `worship-services` | Worship & Services Page |
| Ministries | `ministries` | Ministries Page |
| Events | `events` | Events Page |
| Photos | `photos` | Photos Page |
| Contact Us | `contact-us` | Contact Us Page |

For each page:
1. Enter the **Title** from the table
2. Check the **Permalink** field below the title — edit it to match the **Slug** exactly
3. In the right sidebar under **Page Attributes → Template**, select the matching template
4. Click **Publish**

---

## 4. Verify Slugs

After publishing each page, click **View Page** and confirm the URL matches:

```
http://localhost:8080/about-us/
http://localhost:8080/worship-services/
http://localhost:8080/ministries/
http://localhost:8080/events/
http://localhost:8080/photos/
http://localhost:8080/contact-us/
```

If a URL returns 404, go back to **Settings → Permalinks** and click **Save Changes** again to flush rewrite rules.

---

## 5. Primary Navigation Menu

1. Go to **wp-admin → Appearance → Menus**
2. Click **Create a new menu** — name it `Primary`
3. Add all 7 pages in order
4. Under **Menu Settings**, check **Primary Navigation**
5. Click **Save Menu**

---

## 6. Vehicle Registry & AI Chat Setup

### 6a. Create the Database Table
Run the SQL in `SQL_VEHICLE_REGISTRY.sql` via:
- **phpMyAdmin** → select your WP database → SQL tab → paste & run, or
- **WP-CLI:** `wp db query < SQL_VEHICLE_REGISTRY.sql`

### 6b. Create Azure OpenAI Resource

1. Go to **portal.azure.com** and sign in
2. Click **Create a resource** → search **"Azure OpenAI"** → click **Create**
3. Fill in:
   - **Subscription:** your active subscription
   - **Resource group:** create new or use existing (e.g. `sjioc-rg`)
   - **Region:** `East US` (best GPT-4o availability)
   - **Name:** e.g. `sjioc-openai`
   - **Pricing tier:** `Standard S0`
4. Click **Review + Create** → **Create**
5. Once deployed, click **Go to resource**
6. In the left sidebar click **Keys and Endpoint** — copy:
   - **KEY 1** (your API key)
   - **Endpoint** (e.g. `https://sjioc-openai.openai.azure.com/`)

### 6c. Deploy a GPT-4o Model

1. In your Azure OpenAI resource, click **Go to Azure OpenAI Studio** (or visit oai.azure.com)
2. Click **Deployments** → **Deploy model** → **Deploy base model**
3. Select **gpt-4o** → click **Confirm**
4. Set **Deployment name** to `gpt-4o` → click **Deploy**
5. Wait ~1 minute for deployment to complete

### 6d. Add Credentials to wp-config.php

Add these three lines to `wp-config.php` **before** `/* That's all, stop editing! */`:

```php
define( 'SJIOC_AZURE_OAI_ENDPOINT', 'https://sjioc-openai.openai.azure.com/' );
define( 'SJIOC_AZURE_OAI_KEY',      'paste-key-1-here' );
define( 'SJIOC_AZURE_OAI_DEPLOY',   'gpt-4o' );
```

### 6e. Add Church Knowledge Base (PDF text)

1. Open your church PDF → select all text → copy
2. Go to **wp-admin → SJIOC → Chat Settings**
3. Paste the text into the textarea → click **Save Knowledge Base**

### 6f. Manage Vehicle Registry

1. Go to **wp-admin → SJIOC → Vehicle Registry** *(coming in next build)*
2. Add member vehicles: plate, owner name, phone, vehicle description
3. Any plate number typed in the chat window is looked up against this table automatically

---

## 7. Deploying Theme to Azure (Production Checklist)

> These steps apply when uploading the theme zip to the live Azure WordPress instance.
> The theme code travels with the zip — database content and credentials do not.

### 7a. wp-config.php path on Azure

| Environment | Path |
|---|---|
| Local (OrbStack/Docker) | `/var/www/html/wp-config.php` |
| Azure App Service | `/home/site/wwwroot/wp-config.php` |

On Azure you can edit it via:
- **Azure Portal** → App Service → **SSH** (Development Tools) → navigate to path above, or
- **Kudu console:** `https://<your-app>.scm.azurewebsites.net` → Debug Console → CMD

### 7b. Add credentials to Azure wp-config.php

Add these three lines **before** `/* That's all, stop editing! */`:

```php
define( 'SJIOC_AZURE_OAI_ENDPOINT', 'https://sjioc-openai.openai.azure.com/' );
define( 'SJIOC_AZURE_OAI_KEY',      'paste-key-1-here' );
define( 'SJIOC_AZURE_OAI_DEPLOY',   'gpt-4o' );
```

### 7c. Run vehicle registry SQL on Azure database

1. Get DB credentials from Azure Portal → App Service → **Configuration** → Application Settings
2. Connect via **MySQL Workbench** or **Azure Cloud Shell**:
   ```bash
   mysql -h <db-host> -u <db-user> -p<db-password> <db-name> < SQL_VEHICLE_REGISTRY.sql
   ```

### 7d. Re-enter knowledge base text

1. Log in to **wp-admin** on Azure
2. Go to **SJIOC → Chat Settings**
3. Paste the church PDF text → click **Save Knowledge Base**

### 7e. Post-upload checklist

- [ ] Theme uploaded & activated
- [ ] `SQL_VEHICLE_REGISTRY.sql` run on Azure DB
- [ ] Azure OpenAI credentials added to `wp-config.php`
- [ ] Knowledge base text saved via wp-admin → SJIOC → Chat Settings
- [ ] **Settings → Permalinks** → Save Changes (flush rewrite rules)
- [ ] All 7 pages created with correct slugs and templates
- [ ] Primary nav menu assigned