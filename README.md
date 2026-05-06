# Blog Management System with AJAX Filtering

This is a PHP and MySQL blog management system built for the JobYaari Developer Assessment. It includes a public blog website for visitors and a protected admin panel for managing blog posts.

## Current Status

The website is ready.

Verified locally:

- Public blog listing page returns `200 OK`
- Blog detail page returns `200 OK`
- Admin login page returns `200 OK`
- AJAX category filtering returns blog results without a page reload
- PHP syntax checks pass for the main public and admin files

Local public URL:

```text
http://127.0.0.1:8001/frontend/index.php
```

Admin login URL:

```text
http://127.0.0.1:8001/backend/login.php
```

## Features

### Public User Side

Public users can:

- View all blogs
- Search blogs by title or content
- Filter blogs by category
- Filter blogs by date
- Open a full blog detail page
- Use pagination
- View blog title, image, short description, category, and date

Public users cannot:

- Create blogs
- Edit blogs
- Delete blogs

### Admin Side

Admins can:

- Log in through a simple admin login page
- View the admin dashboard
- Add new blogs
- Edit existing blogs
- Delete blogs
- Upload a cover image for each blog
- Assign a category to each blog
- Manage all blogs from the admin panel

### Rich Content Editor

The create and edit blog pages include a rich editor with:

- Paragraph and heading formats
- Font size
- Undo and redo
- Bold, italic, underline, and strikethrough
- Superscript and subscript
- Text alignment
- Ordered and unordered lists
- Indent and outdent
- Text color and background color
- Link insertion
- Quote block
- Code block
- Horizontal line
- Table insertion
- Image URL insertion
- Clear formatting

## Technical Requirements Covered

- PHP backend
- MySQL database
- HTML and CSS frontend
- Responsive layout for mobile, tablet, and desktop
- jQuery and AJAX filtering without page refresh
- Dynamic blog data fetched from the database
- Admin CRUD for blogs
- Public blog listing and detail pages

## Technologies Used

- PHP
- MySQL
- HTML
- CSS
- jQuery
- AJAX
- Font Awesome
- MAMP local server

## Project Structure

```text
myproject/
  backend/
    admin.php
    analytics.php
    auth.php
    create.php
    db.php
    delete.php
    edit.php
    login.php
    logout.php
    post_media.php
    users.php
  frontend/
    index.php
    blog.php
    style.css
    favicon.svg
    image-1.png
    image-2.png
    image-3.png
    image-4.png
    image-5.png
  uploads/
  README.md
  reorganize.sh
```

## Database Configuration

Database connection is configured in:

```text
backend/db.php
```

Current local settings:

```php
$host = "127.0.0.1";
$user = "root";
$pass = "root";
$db   = "blog";
$port = 8889;
```

## Required Database

Create a database named:

```sql
CREATE DATABASE blog;
```

Minimum `posts` table:

```sql
CREATE TABLE posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'latest-jobs',
  image_path VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Minimum `users` table:

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(190) NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user',
  email_verified_at DATETIME NULL,
  verification_token VARCHAR(128) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

To create an admin account, insert a user with `role = 'admin'` and a PHP `password_hash()` value in the `password` column.

## Setup Instructions

1. Place the project folder in MAMP's `htdocs` directory:

```text
/Applications/MAMP/htdocs/myproject
```

2. Start MAMP and make sure MySQL is running on port `8889`.

3. Create the `blog` database and the required tables.

4. Run the PHP development server from the project root:

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php -S 127.0.0.1:8001 -t /Applications/MAMP/htdocs/myproject
```

5. Open the public website:

```text
http://127.0.0.1:8001/frontend/index.php
```

6. Open the admin login:

```text
http://127.0.0.1:8001/backend/login.php
```

## Main Files

- `frontend/index.php` - Public blog listing, search, category filter, date filter, pagination, AJAX rendering
- `frontend/blog.php` - Public full blog detail page
- `backend/login.php` - Admin login
- `backend/admin.php` - Admin dashboard and blog management table
- `backend/create.php` - Create blog form with rich content editor
- `backend/edit.php` - Edit blog form with rich content editor
- `backend/delete.php` - Delete blog action
- `backend/post_media.php` - Category helpers, image upload helpers, content sanitization
- `frontend/style.css` - Public, admin form, and responsive styles

## Verification Commands

PHP syntax checks:

```bash
/Applications/MAMP/bin/php/php8.4.17/bin/php -l frontend/index.php
/Applications/MAMP/bin/php/php8.4.17/bin/php -l frontend/blog.php
/Applications/MAMP/bin/php/php8.4.17/bin/php -l backend/login.php
/Applications/MAMP/bin/php/php8.4.17/bin/php -l backend/admin.php
/Applications/MAMP/bin/php/php8.4.17/bin/php -l backend/create.php
/Applications/MAMP/bin/php/php8.4.17/bin/php -l backend/edit.php
/Applications/MAMP/bin/php/php8.4.17/bin/php -l backend/delete.php
/Applications/MAMP/bin/php/php8.4.17/bin/php -l backend/post_media.php
```

HTTP checks:

```bash
curl -I http://127.0.0.1:8001/frontend/index.php
curl -I "http://127.0.0.1:8001/frontend/blog.php?id=29"
curl -I http://127.0.0.1:8001/backend/login.php
curl -s "http://127.0.0.1:8001/frontend/index.php?ajax=1&category=latest-jobs"
```

## Notes

- Public visitors do not need to log in.
- Admin-only actions are protected by session and role checks.
- Uploaded blog images are stored in the `uploads/` directory.
- Blog category columns and image columns are also checked by helper code in `backend/post_media.php`.

## Author

Bhanu
