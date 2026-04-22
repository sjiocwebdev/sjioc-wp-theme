# SJIOC Delaware Valley — WordPress Theme v2.0
## Complete Setup Guide: Azure + WordPress

---

## ☁️ AZURE DEPLOYMENT ($2,000 Credit)

### Step 1 — Create WordPress on Azure App Service
1. Sign in to **portal.azure.com**
2. Click **Create a resource** → Search **"WordPress on App Service"**
3. Select the official offering (by Automattic + Azure)
4. Configure:
   - **Subscription:** Your subscription with $2,000 credit
   - **Resource Group:** Create new → `sjioc-dv-rg`
   - **Site Name:** `sjioc-dv` (becomes sjioc-dv.azurewebsites.net)
   - **Region:** East US (closest to Delaware Valley)
   - **App Service Plan:** B2 ($~30/month) → **$2,000 ÷ $30 = 66+ months**
   - **Database:** Azure Database for MySQL Flexible Server (auto-provisioned)
5. Click **Review + Create** → **Create** (5–8 min)

### Step 2 — Custom Domain + Free SSL
1. Azure Portal → Your App Service → **Custom domains**
2. Add your domain (e.g. sjioc.org)
3. **TLS/SSL settings** → Managed Certificate → **Add binding** (FREE)

### Step 3 — Connect Email (Contact Form)
1. Create **Azure Communication Services** resource
2. Add **Email Communication Service** with your domain
3. In WordPress: Install **WP Mail SMTP** → use SMTP credentials from Azure

---

## 🗂️ THEME FILE STRUCTURE

```
sjioc-wp-theme/
├── style.css              ← Theme declaration + all CSS
├── functions.php          ← Setup, CPTs, menus, customizer, AJAX
├── header.php             ← Site header + navigation
├── footer.php             ← Footer + all 3 widget panels + widget bar
├── front-page.php         ← Home page template
├── page-about.php         ← About Us (Template Name: About Us Page)
├── page-worship.php       ← Worship & Services
├── page-ministries.php    ← Ministries
├── page-events.php        ← Events (filterable)
├── page-photos.php        ← Photo Gallery (lightbox)
├── page-contact.php       ← Contact Us (AJAX form)
├── page-worship.php       ← Worship & Services
├── page.php               ← Generic page fallback
├── index.php              ← Blog fallback
└── assets/
    └── js/
        └── main.js        ← All JavaScript (nav, widgets, chat, filters, gallery)
```

---

## 📦 INSTALLING THE THEME

**Option A — WP Admin Upload:**
1. Zip the `sjioc-wp-theme/` folder → `sjioc-wp-theme.zip`
2. WP Admin → **Appearance → Themes → Add New → Upload Theme**
3. Upload → **Activate**

**Option B — SFTP (Recommended for Azure):**
1. App Service → **Deployment Center → FTPS Credentials**
2. Upload theme folder to: `/site/wwwroot/wp-content/themes/sjioc-wp-theme/`
3. WP Admin → Appearance → Themes → Activate

---

## 📄 CREATING PAGES (7 REQUIRED)

Go to **Pages → Add New** for each:

| Page Title          | URL Slug          | Template Name              |
|---------------------|-------------------|----------------------------|
| Home                | (Front page)      | Home Page                  |
| About Us            | about-us          | About Us Page              |
| Worship & Services  | worship-services  | Worship & Services Page    |
| Our Ministries      | ministries        | Ministries Page            |
| Events              | events            | Events Page                |
| Photos              | photos            | Photos / Gallery Page      |
| Contact Us          | contact-us        | Contact Us Page            |

**Set Home Page:**
- **Settings → Reading → "A static page" → Front page: Home**

---

## 🧭 NAVIGATION MENU

1. **Appearance → Menus → Create Menu** → Name: "Primary"
2. Add all 7 pages in order
3. Menu Settings → check **Primary Navigation**
4. Save Menu

Also create a "Footer" menu and assign to **Footer Navigation**.

---

## ⚙️ CUSTOMIZER SETTINGS

**Appearance → Customize → Church Information:**
| Setting             | Value                                                |
|---------------------|------------------------------------------------------|
| Full Church Name    | St. John's Indian Orthodox Church Of Delaware Valley |
| Abbreviation        | SJIOC                                                |
| Address             | 4400 State Road, Drexel Hill, PA 19026               |
| Phone Number        | (610) 822-0033                                       |
| Email Address       | info@sjioc.org                                       |
| Holy Qurbana Time   | 8:30 AM                                              |
| Sunday School Time  | 12:00 PM                                             |
| Saturday Hours      | 5:00 PM – 7:30 PM                                   |
| Facebook URL        | https://facebook.com/sjioc                           |
| YouTube URL         | https://youtube.com/@sjioc                           |
| Google Maps URL     | https://share.google/zTkW7YSgj41LVTwW9              |

---

## 📅 ADDING EVENTS (WordPress Admin)

The theme registers a custom post type **Events**.

1. WP Admin sidebar → **Events → Add New**
2. Set title and description
3. Add **Custom Fields** (use ACF plugin):
   - `event_date` → Date (YYYY-MM-DD)
   - `event_time` → Text (e.g., "8:30 AM")
   - `event_location` → Text (e.g., "Church Grounds")
   - `event_category` → Text (worship / fellowship / education / outreach)
4. Set Featured Image

---

## 🖼️ ADDING GALLERY PHOTOS

1. WP Admin → **Gallery → Add New**
2. Set title and Featured Image
3. Custom Fields:
   - `photo_category` → worship / events / ministries / community
   - `gallery_wide` → 1 (makes item span 2 columns)
   - `gallery_tall` → 1 (makes item span 2 rows)

---

## 👥 PARISH DIRECTORY (Contacts Widget)

1. WP Admin → **Directory → Add New**
2. Set name as title, upload photo as Featured Image
3. Custom Fields:
   - `contact_role` → e.g., "Vicar — SJIOC Delaware Valley"
   - `contact_phone` → e.g., "(610) 822-0033"
   - `contact_email` → e.g., "info@sjioc.org"

If no contacts are added in WP Admin, the theme shows built-in static fallback contacts.

---

## 🎂 CELEBRATIONS (Birthdays & Anniversaries)

1. WP Admin → **Celebrations → Add New**
2. Set name as title
3. Custom Fields:
   - `celeb_type` → bday OR anniv
   - `celeb_day` → Number (1–31)
   - `celeb_mon` → JAN / FEB / MAR / ... / DEC
   - `celeb_note` → e.g., "25th Wedding Anniversary"

Static fallback entries are shown if nothing is added.

---

## 🔌 RECOMMENDED PLUGINS

| Plugin                      | Purpose                              | Cost  |
|-----------------------------|--------------------------------------|-------|
| **Advanced Custom Fields**  | Custom fields for Events, Gallery    | Free  |
| **WP Mail SMTP**            | SMTP email via Azure Comm. Services  | Free  |
| **Yoast SEO**               | Search engine optimization           | Free  |
| **WP Super Cache**          | Page caching for performance         | Free  |
| **UpdraftPlus**             | Automated backups to Azure Blob      | Free  |
| **WordFence**               | Security scanning                    | Free  |
| **Contact Form 7**          | Enhanced forms (optional)            | Free  |

---

## 💰 AZURE COST ESTIMATE

| Service                                | Monthly Cost |
|----------------------------------------|--------------|
| App Service Plan (B2)                  | ~$30         |
| Azure MySQL Flexible Server (B1ms)     | ~$15         |
| Azure CDN Standard                     | ~$5–15       |
| Azure Blob Storage (UpdraftPlus backup)| ~$2          |
| SSL Certificate                        | FREE         |
| **Total**                              | **~$50–62/mo** |

💡 **$2,000 credit ÷ ~$55/month = ~36 months (3 years) of hosting**

For maximum savings: Use App Service Plan **B1** (~$13/mo) = **6+ years** of coverage.

---

## 📱 MOBILE + DESKTOP RESPONSIVENESS

The theme is fully responsive with these breakpoints:
- **Desktop:** 1200px+ — full 3-col grids, side-by-side layouts
- **Tablet:** 960px — 2-col grids, stacked hero sections
- **Mobile:** 640px — single column, full-width panels
- **Small mobile:** 380px — icon-only widget bar tabs

---

## 💬 WIDGET BAR FEATURES

The bottom bar contains 3 interactive widgets:

1. **👥 Contacts** — Searchable parish directory with call/email buttons
2. **🎂 Celebrations** — Birthdays & anniversaries by month, filterable, add new entries, "Wish" button sends to chat
3. **💬 Chat** — Smart parish assistant with auto-replies, quick-reply buttons, typing animation

The bar also shows a scrolling ticker with address, phone, and service times.

---

*Theme by SJIOC Delaware Valley | Version 2.0.0*
