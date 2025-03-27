# Open Library - Book Management System

Open Library is a PHP-based book management system that allows users to browse, search, and read books online. The system includes user authentication, book categorization, personal reading list management, and an admin panel for content management.

## Features

- User registration and authentication
- Browse trending and popular books
- Search functionality to find books by title or author
- Detailed book pages with descriptions and metadata
- Personal reading lists with status tracking (Want to Read, Reading, Read)
- Online book reader using embedded PDF viewer
- Responsive design for mobile and desktop
- User rating system with star ratings and comments
- Admin panel for managing users, books, and site settings
- Direct book upload functionality via UploadThing API

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/MAMP (for local development)

### Installation

1. Clone the repository to your web server's document root (e.g., `htdocs` for XAMPP):

```
git clone https://github.com/yourusername/openlibrary.git
```

2. Set up the database by running the `database.php` script in your browser:

```
http://localhost/OpenLibrary/database.php
```

This will create the database, tables, and populate the initial books.

3. Run the admin user creation script:

```
http://localhost/OpenLibrary/create_admin.php
```

This will set up the admin account with the following credentials:
- Email: tubamirza822@gmail.com
- Password: Tuba@12

4. Configure the site URL in `config.php` if needed:

```php
define('SITE_URL', 'http://localhost/OpenLibrary');
```

5. Make sure your web server has proper permissions to read/write to the project directories.

6. Access the website in your browser:

```
http://localhost/OpenLibrary
```

7. Access the admin panel at:

```
http://localhost/OpenLibrary/admin
```

## Usage

### For Visitors

- Browse books on the homepage
- Search for books using the search bar
- View book details
- Register for an account to access more features

### For Registered Users

- Login to your account
- Add books to your personal reading list
- Update the reading status of books (Want to Read, Reading, Read)
- Read books online in the embedded PDF viewer
- Filter your books by reading status
- Rate books and leave comments on book pages

### For Administrators

- Access the admin panel at `/admin`
- View dashboard with site statistics
- Manage users (edit, delete, admin privileges)
- Manage books (edit, delete, feature in trending/popular)
- Upload new books directly to the library
- Configure site settings

## Book Upload Feature

The admin panel includes a direct book upload feature that uses the UploadThing API. To upload books:

1. Log in to the admin panel
2. Navigate to the "Manage Books" section
3. Click the "Add New Book" button
4. Fill in the book details (title and author will be auto-extracted from the filename)
5. Select a PDF file to upload
6. Click the "Upload" button to upload the file to UploadThing
7. Once the upload is complete, click "Save Book" to save the book to the library

The system automatically generates:
- A thumbnail for the PDF using an embedded viewer
- A cover image for the book

## Database Structure

The system uses four main tables:

1. `users` - Stores user account information
2. `books` - Stores book information and metadata
3. `user_books` - Stores relationships between users and books, including reading status
4. `ratings` - Stores user ratings and comments for books

## Credits

- Book data provided by UploadThing API
- Bootstrap 5 for the UI framework
- Font Awesome for icons

## License

This project is licensed under the MIT License - see the LICENSE file for details. 