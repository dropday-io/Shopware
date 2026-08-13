# Shopware Store Publication Guide

This guide walks you through the process of publishing the Dropday Integration plugin on the [Shopware Store](https://store.shopware.com/).

## Prerequisites

Before you begin, ensure you have:

- [ ] A Shopware Account ([account.shopware.com](https://account.shopware.com))
- [ ] Manufacturer/Partner status (apply at [shopware.com/en/partner](https://www.shopware.com/en/partner/))
- [ ] Completed and tested plugin
- [ ] Plugin icon (128x128 PNG)
- [ ] Screenshots of the plugin in action
- [ ] Plugin documentation

## Step 1: Prepare Your Plugin

### 1.1 Validate Plugin Structure

Ensure your plugin follows Shopware's requirements:

```
DropdayIntegration/
├── composer.json              # Required: Package metadata
├── src/
│   ├── DropdayIntegration.php # Required: Main plugin class
│   └── Resources/
│       ├── config/
│       │   ├── config.xml     # Plugin configuration
│       │   ├── services.xml   # Service definitions
│       │   └── routes.xml     # API routes
│       └── app/
│           └── administration/
│               └── src/       # Admin JS/Vue components
└── README.md
```

### 1.2 Update composer.json

Ensure all metadata is complete:

```json
{
    "name": "dropday/shopware-integration",
    "description": "Dropday.io integration for Shopware 6 - Automated order fulfillment",
    "version": "1.0.0",
    "type": "shopware-platform-plugin",
    "license": "MIT",
    "extra": {
        "shopware-plugin-class": "Dropday\\DropdayIntegration\\DropdayIntegration",
        "label": {
            "de-DE": "Dropday Integration",
            "en-GB": "Dropday Integration"
        },
        "description": {
            "de-DE": "...",
            "en-GB": "..."
        },
        "manufacturerLink": {
            "de-DE": "https://dropday.io",
            "en-GB": "https://dropday.io"
        },
        "supportLink": {
            "de-DE": "https://dropday.io/support",
            "en-GB": "https://dropday.io/support"
        }
    }
}
```

### 1.3 Run Plugin Validation

```bash
# Install Shopware CLI
composer global require shopware/cli

# Validate plugin
shopware-cli extension validate DropdayIntegration/
```

### 1.4 Create Distribution Package

```bash
# Build administration assets
cd custom/plugins/DropdayIntegration
bin/build-administration.sh

# Create ZIP (exclude dev files)
cd ..
zip -r DropdayIntegration-1.0.0.zip DropdayIntegration \
    -x "*.git*" \
    -x "*node_modules*" \
    -x "*.env*" \
    -x "*docker*" \
    -x "*.md" \
    -x "*tests*"
```

## Step 2: Create Store Listing

### 2.1 Access Producer Portal

1. Log in to [account.shopware.com](https://account.shopware.com)
2. Navigate to **Producer Portal** → **Extensions**
3. Click **Create Extension**

### 2.2 Basic Information

Fill in the following:

| Field | English Value | German Value |
|-------|---------------|--------------|
| **Name** | Dropday Integration | Dropday Integration |
| **Short Description** | Automate order fulfillment with Dropday.io | Automatisieren Sie die Auftragsabwicklung mit Dropday.io |
| **Category** | Fulfillment & Shipping | Fulfillment & Shipping |
| **Tags** | dropshipping, fulfillment, automation, orders | Dropshipping, Fulfillment, Automatisierung, Bestellungen |

### 2.3 Long Description (Store Page)

**English:**

```markdown
# Dropday Integration for Shopware 6

Seamlessly connect your Shopware store with Dropday.io for automated order fulfillment.

## Key Features

✅ **Automatic Order Sync** - Orders are automatically sent to Dropday when payment is received
✅ **Flexible Triggers** - Configure when orders are sent (Open, Paid, In Progress)
✅ **Manual Sync** - Send individual orders with one click
✅ **Multi-Channel Support** - Different settings per sales channel
✅ **Complete Data Transfer** - Customer data, shipping, products, and pricing

## How It Works

1. Install and configure the plugin with your Dropday API credentials
2. Set your preferred trigger state
3. Orders are automatically sent to Dropday for fulfillment

## Requirements

- Shopware 6.5 or 6.6
- Dropday.io account

## Support

- Documentation: docs.dropday.io
- Email: support@dropday.io
```

**German:**

```markdown
# Dropday Integration für Shopware 6

Verbinden Sie Ihren Shopware-Shop nahtlos mit Dropday.io für automatisierte Auftragsabwicklung.

## Hauptfunktionen

✅ **Automatische Bestellsynchronisation** - Bestellungen werden automatisch an Dropday gesendet
✅ **Flexible Auslöser** - Konfigurieren Sie, wann Bestellungen gesendet werden
✅ **Manuelle Synchronisation** - Einzelne Bestellungen mit einem Klick senden
✅ **Multi-Channel-Unterstützung** - Verschiedene Einstellungen pro Verkaufskanal
✅ **Vollständige Datenübertragung** - Kundendaten, Versand, Produkte und Preise

## So funktioniert es

1. Installieren und konfigurieren Sie das Plugin mit Ihren Dropday API-Zugangsdaten
2. Legen Sie Ihren bevorzugten Auslöserstatus fest
3. Bestellungen werden automatisch zur Abwicklung an Dropday gesendet

## Voraussetzungen

- Shopware 6.5 oder 6.6
- Dropday.io-Konto

## Support

- Dokumentation: docs.dropday.io
- E-Mail: support@dropday.io
```

### 2.4 Screenshots

Upload at least 3 screenshots:

1. **Configuration Page** - Show the API settings
2. **Dashboard** - Show the connection test feature
3. **Order Detail** - Show the manual sync button

**Screenshot Requirements:**
- Minimum 800x600 pixels
- PNG or JPG format
- No personal data visible
- Clean, professional appearance

### 2.5 Plugin Icon

- Size: 128x128 pixels
- Format: PNG with transparency
- Design: Match Dropday branding (orange #ff6b35)

## Step 3: Pricing & Licensing

### 3.1 Choose Pricing Model

Options available:

| Model | Description |
|-------|-------------|
| **Free** | No charge, good for adoption |
| **One-time Purchase** | Single payment |
| **Subscription** | Monthly/yearly recurring |
| **Freemium** | Free with premium features |

**Recommendation:** Start with **Free** to maximize adoption and align with Dropday's business model (revenue from fulfillment, not software).

### 3.2 License Selection

- **Proprietary** - Full control over distribution
- **MIT** - Open source, allows modifications

## Step 4: Upload Plugin Binary

### 4.1 Create New Version

1. Go to **Versions** tab
2. Click **Create Version**
3. Enter version number: `1.0.0`
4. Upload the ZIP file

### 4.2 Version Requirements

- Include changelog
- Specify compatible Shopware versions: `6.5.0 - 6.6.x`
- Note any breaking changes

### 4.3 Changelogs

**English:**
```
Version 1.0.0
- Initial release
- Automatic order synchronization
- Manual order sync
- Multi-language support (EN, DE)
- Sales channel configuration
```

**German:**
```
Version 1.0.0
- Erstveröffentlichung
- Automatische Bestellsynchronisation
- Manuelle Bestellsynchronisation
- Mehrsprachige Unterstützung (EN, DE)
- Verkaufskanal-Konfiguration
```

## Step 5: Quality Review

### 5.1 Automated Checks

Shopware runs automated checks for:

- [ ] Valid plugin structure
- [ ] No deprecated APIs
- [ ] Code quality (PSR standards)
- [ ] Security vulnerabilities
- [ ] Compatible Shopware versions

### 5.2 Manual Review

A Shopware team member reviews:

- [ ] Plugin functionality
- [ ] User experience
- [ ] Documentation quality
- [ ] Store listing accuracy

### 5.3 Review Timeline

- **Automated checks:** ~1 hour
- **Manual review:** 3-5 business days
- **Revisions:** Additional 1-2 days per revision

## Step 6: Publication

### 6.1 Final Checklist

Before submitting for review:

- [ ] All required fields completed
- [ ] Screenshots uploaded
- [ ] Plugin binary uploaded
- [ ] Pricing configured
- [ ] Support contact verified
- [ ] Legal information complete

### 6.2 Submit for Review

1. Click **Submit for Review**
2. Wait for approval email
3. Address any feedback
4. Once approved, click **Publish**

## Step 7: Post-Publication

### 7.1 Monitor Feedback

- Check reviews regularly
- Respond to questions
- Track download statistics

### 7.2 Maintenance

- Release bug fixes promptly
- Update for new Shopware versions
- Add requested features

### 7.3 Marketing

- Announce on Dropday channels
- Create blog posts
- Engage in Shopware community

## Appendix: Store Listing Checklist

```
□ Plugin Name (EN/DE)
□ Short Description (EN/DE) - max 150 characters
□ Long Description (EN/DE) - formatted markdown
□ Category selected
□ Tags added (min 3)
□ Plugin icon (128x128 PNG)
□ Screenshots (min 3)
□ Video URL (optional)
□ Documentation link
□ Support email/URL
□ Changelog
□ License type
□ Pricing model
□ Compatible Shopware versions
□ PHP version requirements
□ Plugin binary (.zip)
```

## Resources

- [Shopware Plugin Guidelines](https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/plugin-meta-information)
- [Store Documentation](https://docs.shopware.com/en/plugin-standard-for-community-store)
- [Plugin Quality Guidelines](https://developer.shopware.com/docs/resources/guidelines/code/quality-guidelines-plugins)
- [Shopware Partner Portal](https://www.shopware.com/en/partner/)

## Support

Need help with publication?

- Shopware Partner Support: [shopware.com/en/support](https://www.shopware.com/en/support/)
- Community Forum: [forum.shopware.com](https://forum.shopware.com/)
- Dropday Team: [support@dropday.io](mailto:support@dropday.io)

---

**Good luck with your Store submission!** 🚀

