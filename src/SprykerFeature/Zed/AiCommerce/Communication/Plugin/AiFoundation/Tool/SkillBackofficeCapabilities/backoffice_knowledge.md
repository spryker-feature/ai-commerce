# Spryker Backoffice Capabilities

## Sales
**Orders**: list/view/edit orders; edit addresses & customer info; trigger OMS events on items/orders; add comments; export CSV; create/edit shipments
**Returns**: create returns; view list/details; generate return slip; trigger return item OMS events
**Reclamations**: create/view/close reclamations against order items; trigger OMS transitions
**Order Matrix**: order counts grouped by item state for operational monitoring
**Refunds**: view/track refund records per order
**Gift Card Balance**: view gift card balances

## Customers
**Customers**: CRUD customer accounts; manage addresses; account status
**Customer Groups**: CRUD groups for segmentation; bulk customer assignment
**Customer Access**: configure guest vs. registered content access
**Companies**: CRUD company accounts
**Company Units**: hierarchical business units (departments/divisions); parent-child relationships
**Company Unit Addresses**: addresses per business unit
**Company Users**: link customers to companies; manage roles/settings
**Company Roles**: CRUD roles with granular permission sets

## Catalog
**Products**: CRUD abstract products & variants; manage pricing (multi-locale, dimensions); stock/warehouse config; bundles; configurable products; tab-based extensible UI
**Product Offers**: list/edit merchant offers (pricing, inventory, availability)
**Category**: CRUD categories with hierarchy; reorder; multi-locale
**Attributes**: CRUD product attributes & value translations per locale
**Availability**: view/edit stock per warehouse/store; never-out-of-stock; bundle availability
**Product Reviews**: view/moderate customer reviews
**Product Options**: configure additional options (gift wrap, engraving)
**Product Lists**: whitelist/blacklist product lists for pricing/cart rules
**Product Barcodes**: view/manage barcode assignments
**Scheduled Prices**: time-limited automatic price changes

## Content
**Content Items**: manage reusable content entities (banners, product lists, file lists)
**CMS Blocks**: CRUD reusable content blocks; multi-locale placeholders
**CMS Pages**: CRUD content pages; template selection; version management; activate/deactivate
**CMS Slots**: assign blocks to template slots; reorder; flexible layout management
**Navigation**: CRUD hierarchical navigation trees; link to categories/pages/URLs; store-specific
**Redirects**: manage URL redirects
**File List/Tree**: upload/organize media assets; MIME type config

## Merchandising
**Discounts**: CRUD discounts (fixed/percentage, cart/voucher); applicability rules; voucher code generation
**Product Labels**: CRUD labels (e.g. "New", "Sale"); assign to products
**Product Relations**: CRUD cross-sell/upsell rules; dynamic query builder or static assignment
**Product Sets**: curated product sets for campaigns
**Configurable Bundle Templates**: slot-based bundle templates
**Search Preferences**: product search indexing and boosting config
**Filter Preferences**: faceted search filter config
**Category Filters**: per-category filter assignment

## Marketplace
**Merchants**: onboard/edit merchants; activate/deactivate; manage registrations/approvals
**Merchant Relations**: CRUD B2B merchant-company relationships
**Merchant Relation Requests**: review/process requests
**Merchant Commissions**: CRUD commission rules; bulk import/export

## Users
**Users**: CRUD Backoffice users; activate/deactivate; password reset; assign ACL groups/roles
**User Roles (ACL)**: CRUD roles with resource-level permission rules
**User Groups (ACL)**: CRUD groups; assign roles and users

## Administration
**Stores**: store config (locales, currencies, countries)
**Measurement Units**: product units of measurement
**Warehouses**: CRUD warehouse locations
**Payment Methods**: view/configure payment providers; enable/disable
**Delivery Methods**: CRUD shipment methods and carriers; delivery zones/pricing
**Glossary**: manage translation keys per locale; bulk translation
**Tax Rates/Sets**: CRUD tax rates by country; group into sets; assign to products
**Order Thresholds**: global and merchant-relationship minimum order thresholds (hard/soft)
**OMS**: visualize state machine diagrams; view order/item process flows; trigger manual events; monitor queues; preview versions
**State Machine**: manage non-OMS state machines
**API Keys**: manage Backoffice API keys

## Intelligence (AI Foundation)
**Workflows**: view/manage AI workflow items; trigger manual events; state machine lifecycle
**Audit Logs**: view AI interaction logs, stats, and per-interaction details

## Apps
App catalog for third-party app connections and configurations

## Data Exchange API
Configure dynamic entity REST API endpoints (expose DB tables without custom code)

## Maintenance
Module overview, dependency graphs, stability analysis, architecture sniffers; PHP/system info; plugin registry; ACL permission sync; Elasticsearch index management; RabbitMQ queue monitoring; Redis storage inspection; dataset import/export

## Analytics
Business reporting dashboard (orders, revenue metrics)

## Dashboard
Key operational metrics overview
