# Spryker Discount Knowledge Base

## Overview

Discounts define promotional price reductions applied to cart items or the entire order.
Navigation: Merchandising > Discount.

**Discount Types:**
- **Cart rule** — Applied automatically when cart conditions are met. No voucher code required by the customer.
- **Voucher codes** — Applied only when the customer enters a valid voucher code at checkout. Requires voucher code generation.

**Discount Lifecycle:**
1. Create discount (starts as inactive)
2. Configure all tabs (General Information, Discount calculation, Conditions; and Voucher codes for voucher type)
3. Activate via the "Activate" button to make it live
4. Deactivate at any time via the "Deactivate" button

---

## Tab 1: General Information

Defines the basic properties of the discount.

| Field | Required | Description |
|---|---|---|
| Store relation | No | Select which stores the discount applies to (e.g. DE, AT). Multiple stores can be selected. |
| DISCOUNT TYPE | Yes | `Cart rule` or `Voucher codes` |
| NAME | Yes | Unique, short, descriptive name. Displayed in cart calculation to the customer. Must be unique across all discounts. |
| DESCRIPTION | No | Summary explaining the promotion. Displayed with eligible products where applicable. |
| PRIORITY | No | Integer 1–9999. Applied in sequential order starting from 1. Default is 9999 (lowest priority). Lower number = higher priority = applied first. |
| NON-EXCLUSIVE / EXCLUSIVE | Yes | **NON-EXCLUSIVE** (default): can be combined with other discounts. **EXCLUSIVE**: prevents all other discounts from applying simultaneously. |
| VALID FROM (TIME IN UTC) | Yes | Start date/time. Format: `DD.MM.YYYY HH:MM`. Example: `01.01.2026 00:00` |
| VALID TO (TIME IN UTC) | Yes | End date/time. Format: `DD.MM.YYYY HH:MM`. Example: `31.12.2026 23:59` |

---

## Tab 2: Discount Calculation

Defines WHAT items receive the discount and HOW MUCH discount is applied.

### Calculator Type

| Value | Description |
|---|---|
| `Percentage` | Discount is a percentage (0.01–100). Example: `10` = 10% off. |
| `Fixed amount` | Discount is a fixed monetary amount in the smallest currency unit (e.g. cents). Example: `500` = €5.00. |

### Discount Application Type

| Value | Description |
|---|---|
| `QUERY STRING` | Use a rule builder or plain query string to select which cart items receive the discount. |
| `PROMOTIONAL PRODUCT` | Add a specific product to the cart at a discounted price by entering its SKU. |

### APPLY TO (Query String mode)

Defines which items in the cart receive the discount. Uses field/operation/value rules combined with AND/OR logic. Leave empty to apply to all items.

Switch between **Query builder** (visual dropdown mode) and **Plain query** (raw text mode) via the button.

---

## Tab 3: Conditions

Defines WHEN the discount becomes eligible. All conditions must be true before any discount is applied.

| Field | Required | Description |
|---|---|---|
| APPLY WHEN | No | Rule set using the same query string syntax as Discount Calculation. Leave empty to apply unconditionally. |
| THE DISCOUNT CAN BE APPLIED IF THE QUERY APPLIES FOR AT LEAST X ITEM(S) | Yes | Minimum number of matching items required. Default: `1`. |

---

## Tab 4: Voucher Codes (Voucher codes type only)

| Field | Required | Description |
|---|---|---|
| QUANTITY | Yes | Number of codes to generate. Integer 1–14000. |
| CUSTOM CODE | No | Base string for the code. Use `[code]` placeholder to indicate where random characters are inserted. Example: `SUMMER[code]` |
| ADD RANDOM GENERATED CODE LENGTH | No | Number of random characters appended. Options: No additional random characters, 3, 4, 5, 6, 7, 8, 9, 10. |
| MAX NUMBER OF USES (0 = INFINITE USAGE) | Yes | How many times each code can be used. `0` = unlimited. Default: `0`. |

Generated codes appear in a table with columns: Voucher Code, Used, Maximum number of uses, Created At, Batch Value.
Actions: Export all codes to CSV, Delete all codes, Delete individual code.

---

## Query String Syntax

Both "APPLY TO" (Discount Calculation) and "APPLY WHEN" (Conditions) use the same syntax.

### Basic Syntax

```
field = "value"
field != "value"
field is in "value1;value2;value3"
field is not in "value1;value2"
field > "value"
field >= "value"
field < "value"
field <= "value"
```

### Combining Rules

```
(field1 = "val1") AND (field2 = "val2")
(field1 = "val1") OR (field1 = "val2")
((field1 = "val1") AND (field2 = "val2")) OR (field3 = "val3")
```

### Wildcard

`field = "*"` — matches all items that have this field defined, regardless of value.

### Examples

| Field | Operation | Value | Explanation |
|---|---|---|---|
| sku | equal | SKU-1 | Exact SKU match |
| attribute.width | equal | * | Items with "width" attribute defined |
| sku | in | SKU-1;SKU-2 | SKU is SKU-1 or SKU-2 |
| item-price | greater or equal | 50000 | Items priced €500+ (value in cents) |
| category | equal | 5 | Items in category ID 5 |
| grand-total | greater or equal | 10000 | Cart total €100+ |

---

## Collector Fields (APPLY TO — Discount Calculation tab)

Fields available for selecting which items receive the discount:

| Field | Description |
|---|---|
| `sku` | Product SKU |
| `item-quantity` | Item quantity in cart |
| `item-price` | Unit price of the item |
| `product-label` | Product label assigned to the item |
| `shipment-carrier` | Shipment carrier name |
| `shipment-method` | Shipment method name |
| `shipment-price` | Shipment price |
| `category` | Category ID |
| `product-offer-reference` | Marketplace product offer reference |
| `merchant-reference` | Marketplace merchant reference |
| `attribute.*` | Any product attribute by key. Example: `attribute.color`, `attribute.brand` |

---

## Condition Fields (APPLY WHEN — Conditions tab)

Fields available for defining when the discount is eligible:

| Field | Description |
|---|---|
| `sku` | Item SKU present in cart |
| `currency` | Cart currency ISO code. Example: `EUR` |
| `price-mode` | Pricing mode: `NET_MODE` or `GROSS_MODE` |
| `grand-total` | Cart grand total in smallest currency unit (cents). Example: `10000` = €100 |
| `sub-total` | Cart subtotal before taxes/shipping |
| `total-quantity` | Total quantity of all items in cart |
| `item-quantity` | Quantity of a specific item |
| `item-price` | Unit price of an item |
| `calendar-week` | Current calendar week (1–53) |
| `day-of-week` | Day of week: 1=Monday, 7=Sunday |
| `month` | Current month (1–12) |
| `time` | Current time HH:MM. Example: `time >= "09:00"` |
| `shipment-carrier` | Selected shipment carrier |
| `shipment-method` | Selected shipment method |
| `shipment-price` | Shipment price |
| `customer-group` | Customer group name |
| `product-label` | Product label on cart items |
| `category` | Category ID of cart items |
| `customer-order-count` | Total orders the customer has placed |
| `customer-reference` | Specific customer reference |
| `maximum-uses-per-customer` | Max times this customer can use the discount. Example: `1` = single-use per customer |
| `product-offer-reference` | Marketplace product offer reference |
| `merchant-reference` | Marketplace merchant reference |
| `attribute.*` | Product attribute. Example: `attribute.brand = "Apple"` |
