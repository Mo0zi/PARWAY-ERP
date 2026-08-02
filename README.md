# 👔 PARWAY ERP — Garments & Clothing Manufacturing ERP System

Parway ERP is a web-based Enterprise Resource Planning (ERP) application engineered specifically for Clothing Factories, Stitching Units, and Apparel Manufacturers.

## 🚀 Key Features
- **Dashboard & Business Control Panel**: Real-time sales, worker wages, customer receivables, worker payables, 7-day performance graphs, lot completion progress, and worker leaderboard.
- **Sales & Invoicing Module**: Sequential invoice numbering, customer auto-suggest, mandatory address pre-fill (`Sialkot, Punjab`), discount clamping (`min="0"`), dynamic Red/Green balance indicators, single-tab WhatsApp Web sharing, and A4 print/PDF export.
- **Manufacturing & Working Sheet (Lot) Module**: Excel-like multi-worker job allocation, multi-day **Lot Editing (`lot_edit.php`)**, interactive AJAX lot breakdown, printable lot sheets, and Excel exporter.
- **Workers & Piece-Rate Wages**: Worker catalog, running worker ledgers, net payable badges, and **Printable Salary & Advance Payment Slips (`salary_slip.php`)** with worker thumb impression block and WhatsApp sharing.
- **Customers & Sales Ledger**: Customer directory with addresses and running receivable balance tracking (`Maal Diya` balance).
- **Personal Ledger & Analytics**: Owner income/expense tracking, daily/monthly sales & wage reports, and wage analyzer.
- **System Backup & Export**: Master PDF Backup Report generator & MySQL SQL Backup file export.
- **Ultimate Customizer Suite**: Dynamic primary/secondary/sidebar color pickers, 4 preset palettes, currency symbol switcher, logo upload, custom invoice titles, and work types.

## 🛠️ Technology Stack
- **Backend**: Native PHP 8.x (PDO)
- **Database**: MySQL / MariaDB (Automatic Schema & Migration Engine)
- **Frontend**: Bootstrap 5, FontAwesome 6, jQuery, Chart.js, Vanilla CSS

## 📋 Installation & Setup

1. **Clone Repository**:
   ```bash
   git clone https://github.com/Mo0zi/PARWAY-ERP.git
   ```

2. **Database Setup**:
   - Create a MySQL database named `parway_erp`.
   - Import the database schema from `sql/parway_erp.sql`.

3. **Database Configuration**:
   - Update your MySQL credentials in `config/db.php`:
     ```php
     private $host = '127.0.0.1';
     private $user = 'root';
     private $pass = '';
     private $name = 'parway_erp';
     ```

4. **Default Login Credentials**:
   - **Email / Username**: `parway2002@gmail.com`
   - **Password**: `SHAHBAZ@000`

---
Developed for Parway Industry & Clothing Manufacturing Units.
