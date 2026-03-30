# Spryker Order Management Knowledge Base

## Overview

Orders represent customer purchases. Navigation: Sales > Orders.
Order detail page URL: `/sales/detail?id-sales-order={idOrder}`

**Order List filters:**
- STATUS — filter by OMS state
- STORE — filter by store (DE, AT, etc.)
- ORDER DATE FROM / TO — date range filter

**Order list columns:** #, Order Reference, Created, Customer Full Name, Email, Order State, Grand Total, Actions.
Actions per row: **View** (read-only detail), **Create Reclamation** (raise a reclamation against an order).

---

## Order Detail Page Sections

### Order Overview
Top-level summary of the order.

| Field | Description |
|---|---|
| Order Reference | Unique order identifier (e.g. `DE--790698-300539-9536`) |
| Order date | Date and time the order was placed |
| Unique Product Quantity | Number of distinct product variants |
| Subtotal | Items total before discounts and shipping |
| Shipment | Shipping method name and cost |
| Discount | Total discount amount applied |
| GRAND TOTAL | Final amount the customer paid |
| Total taxes | Tax amount included in grand total |
| Refund total | Total amount refunded |
| Total Commission | Marketplace commission total |
| Total Refunded Commission | Marketplace refunded commission total |

### Trigger All Matching States (Order Level)
Buttons that trigger OMS events on ALL eligible items in the order simultaneously.
Only events applicable to the current state of each item are shown.
Example buttons: **Cancel**, **Skip grace period**.

### Custom Order Reference
Optional internal reference field editable by admin users.
Click **Edit Reference** to open the field, then **Save** or **Cancel**.

### Customer
| Field | Description |
|---|---|
| Reference | Customer ID (clickable link to customer detail) |
| Name | Customer full name |
| Email | Customer email address |
| Previous orders count | Total orders the customer has placed |
| Billing address | Full billing address (editable via Edit button) |

### Order Items
Lists all items in the order, grouped by shipment.

**Per shipment group:**
- Delivery Address
- Delivery Method and Shipping Method name
- Shipping Costs
- Request delivery date
- **Edit Shipment** button — edit delivery address, method, delivery date
- **Trigger all matching states of order inside this shipment** — bulk OMS event trigger for this shipment's items only

**Per order item:**
- Product image, name (clickable link to product), SKU
- Variant Details (attributes like color, size)
- Quantity
- Unit Price (discounted / original, incl. tax %)
- Item total
- **State** — current OMS state name (clickable link to OMS state machine visualizer) with process name
- **Show history** — view the state transition history for this item
- **Trigger event buttons** — available manual OMS events for this item (e.g. Cancel, Skip grace period, Ship, Deliver)

**Create Shipment** button — split order into additional shipment.

### Cart Notes
Free-text note attached to the cart/order by the customer. Shown as "Order has no notes" if empty.

### Returns
Table of returns created for this order.
Columns: Return Reference, Items, Remuneration total, Actions.
**Return** button (top right of order detail) — create a new return for this order.

### Comments to Order
Admin-visible comments thread. Each comment shows author and timestamp.
"Order has no comments" if empty.

### Bundle Items Cart Notes
Notes for bundle items specifically. Shown as "Order has no notes" if empty.

### Payments
| Column | Description |
|---|---|
| Payment provider | Payment provider name (e.g. Dummy Payment, PayPal) |
| Payment method | Payment method name (e.g. dummyPaymentInvoice, credit card) |
| Amount | Amount charged via this payment method |

### Payment Metadata
Additional payment provider transaction details: Transaction Date, Transaction ID, Details.

### Gift Cards
Gift cards applied to this order: Gift card name, Code, Amount.

### Discounts & Vouchers
All discounts and voucher codes applied to this order.
| Column | Description |
|---|---|
| Type | `Discount` (cart rule) or `Voucher` |
| Name | Discount name |
| Code | Voucher code (or `-` for cart rules) |
| Amount | Discount amount applied |
| Description | Discount description |

### Refunds
List of refunds issued for this order. Shows "No returns for this order" if none.

### Order Source
Origin channel of the order (e.g. web, mobile, API). Shown as `-` if not tracked.

### Inquiries
Customer service inquiries linked to this order.
Columns: Reference, Subject, Status, Actions.

### Comments
Admin comment input form at the bottom of the page.
Field: MESSAGE (required). Button: **Send Message**.

---

## OMS (Order Management System)

The OMS manages item-level state transitions through a state machine process.

### Key Concepts

| Concept | Description |
|---|---|
| State | Current lifecycle position of an order item (e.g. `new`, `payment pending`, `exported`, `grace period started`, `shipped`, `delivered`, `cancelled`) |
| Process | The state machine process definition the item follows (e.g. `DummyPayment01`) |
| Transition | Automatic movement between states triggered by conditions/timeouts |
| Event | Named trigger that causes a state transition. Can be automatic or manual. |
| Manual event | An event that requires human or external system action to trigger (shown as buttons in the Backoffice) |
| Timeout | Automatic transition after a time period (e.g. after 1 hour in `grace period started`, auto-advance) |
| Flag | Boolean markers on states indicating special behavior (e.g. `exclude from customer` to hide from customer-facing views) |

### State Levels
- **Order level** — aggregate view. "Trigger all matching states inside this order" applies events to all eligible items.
- **Shipment level** — triggers apply to all eligible items within a specific shipment group.
- **Item level** — individual item event triggers shown per row.

### Reading an Item State
Each item shows:
- State name (e.g. `grace period started`) — clickable link opens the OMS state machine diagram with that state highlighted
- Process name (e.g. `DummyPayment01`) — the state machine definition the item follows
- Available event buttons for manual triggering

### Common OMS States (DummyPayment01 example)
| State | Meaning |
|---|---|
| `new` | Order just placed, awaiting payment confirmation |
| `payment pending` | Waiting for payment provider confirmation |
| `payment failed` | Payment was declined |
| `exported` | Order exported to ERP/fulfillment system |
| `grace period started` | Brief window during which the customer can cancel |
| `shipped` | Physical shipment dispatched |
| `delivered` | Customer confirmed receipt or delivery confirmed |
| `cancelled` | Order item cancelled |
| `refunded` | Refund issued for this item |

### Manual Events
Manual events are admin-triggered OMS transitions. They appear as buttons next to each item.
Examples: **Cancel**, **Skip grace period**, **Ship**, **Deliver**, **Pay**, **Close**.
Not all events are available at all states — only those valid for the current state are shown.

---

## Order Actions Reference

| Action | Location | Description |
|---|---|---|
| View order | Orders list → View | Open order detail page |
| Create Reclamation | Orders list → Create Reclamation | Raise reclamation against an order |
| Trigger order-level event | Order Overview section | Apply OMS event to all eligible items |
| Trigger shipment-level event | Shipment section | Apply OMS event to all items in shipment |
| Trigger item-level event | Per item row | Apply OMS event to single item |
| Edit billing address | Customer section → Edit | Change billing address |
| Edit Custom Order Reference | Custom Order Reference section | Set internal reference |
| Create Shipment | Order Items section | Split order into new shipment |
| Edit Shipment | Shipment heading → Edit Shipment | Edit delivery address, method, date |
| Create Return | Top right button "Return" | Create return for order items |
| Add admin comment | Comments section | Add internal comment to order |
| Download CSV | Orders list → Download as CSV | Export filtered order list |

---

## Returns

Returns allow customers (or admins) to send items back.
- Navigate to order detail → click **Return** (top right)
- Select items and quantities to return
- Returns appear in the Returns section of the order detail
- Return list available at: Sales > Returns (`/sales-return-gui`)

---

## Reclamations

Reclamations are internal issue tickets against an order.
- Created from the Orders list → **Create Reclamation** button
- Managed at: Sales > Reclamations (`/sales-reclamation-gui`)
- Can be closed once resolved

---

## Refunds

Refunds are issued after a return or cancellation is processed through OMS.
- Shown in the Refunds section of the order detail
- Full refund history at: Sales > Refunds (`/refund/table`)

---

## Key URLs

| Page | URL |
|---|---|
| Orders list | `/sales` |
| Order detail | `/sales/detail?id-sales-order={idOrder}` |
| Edit billing address | `/sales/edit/address?id-sales-order={idOrder}&id-address={idAddress}` |
| Create Shipment | `/shipment-gui/create?id-sales-order={idOrder}` |
| Edit Shipment | `/shipment-gui/edit?id-sales-order={idOrder}&id-shipment={idShipment}` |
| Create Return | `/sales-return-gui/create?id-order={idOrder}` |
| Returns list | `/sales-return-gui` |
| Reclamations list | `/sales-reclamation-gui` |
| Refunds list | `/refund/table` |
| Order Matrix | `/order-matrix-gui/matrix` |
| OMS visualizer | `/state-machine-visualizer/index/render?state-machine-type=oms&process={processName}` |
