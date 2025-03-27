<?php
// Database Configuration
$host = '127.0.0.1';
$user = 'root';
$password = '';
$db_name = 'openlibrary';

// Create connection - with error reporting
try {
    // Create connection
    $conn = new mysqli($host, $user, $password);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to MySQL successfully.<br>";
    
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    if ($conn->query($sql) === FALSE) {
        die("Error creating database: " . $conn->error);
    }
    
    echo "Database '$db_name' created or already exists.<br>";
    
    // Select the database
    $conn->select_db($db_name);
    
    // Check if tables exist first
    $result = $conn->query("SHOW TABLES LIKE 'ratings'");
    $ratings_exists = $result->num_rows > 0;
    
    $result = $conn->query("SHOW TABLES LIKE 'user_books'");
    $user_books_exists = $result->num_rows > 0;
    
    // Drop tables in correct order to handle foreign key constraints
    if ($ratings_exists) {
        $conn->query("DROP TABLE IF EXISTS ratings");
        echo "Dropped ratings table to handle constraints.<br>";
    }
    
    if ($user_books_exists) {
        $conn->query("DROP TABLE IF EXISTS user_books");
        echo "Dropped user_books table to handle constraints.<br>";
    }
    
    // Create tables
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating users table: " . $conn->error);
    }
    
    echo "Table 'users' created successfully.<br>";
    
    $sql = "CREATE TABLE IF NOT EXISTS books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(100) NOT NULL,
        file_key VARCHAR(255) NOT NULL,
        file_url VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        uploaded_at DATETIME NOT NULL,
        cover_image VARCHAR(255) DEFAULT NULL,
        thumbnail_url VARCHAR(255) DEFAULT NULL,
        description TEXT,
        is_trending BOOLEAN DEFAULT FALSE,
        is_popular BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating books table: " . $conn->error);
    }
    
    echo "Table 'books' created successfully.<br>";
    
    // Clear existing books to avoid duplicates - now safe to do this
    $conn->query("TRUNCATE TABLE books");
    echo "Books table truncated.<br>";
    
    $sql = "CREATE TABLE IF NOT EXISTS user_books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        status ENUM('to_read', 'reading', 'read') DEFAULT 'to_read',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating user_books table: " . $conn->error);
    }
    
    echo "Table 'user_books' created successfully.<br>";
    
    // Create ratings table
    $sql = "CREATE TABLE IF NOT EXISTS ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        UNIQUE KEY user_book_rating (user_id, book_id)
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating ratings table: " . $conn->error);
    }
    
    echo "Table 'ratings' created successfully.<br>";
    
    // Insert books from JSON data
    $books_json = '[
      {
        "name": "Osho,_Osho_International_Foundation_Life_is_a_soap_bubble_100_ways.pdf",
        "key": "e3MIREz0UMEmsPkOeFUJidnY6xPWSbzCm3hEgGlBvpAOQcyw",
        "customId": null,
        "url": "https://tgo15bqb07.ufs.sh/f/e3MIREz0UMEmsPkOeFUJidnY6xPWSbzCm3hEgGlBvpAOQcyw",
        "size": 2846416,
        "uploadedAt": "2025-03-19T07:27:11.000Z"
      },
      {
        "name": "Ankur Warikoo - Get Epic Shit Done-Juggernaut Books (2022).pdf",
        "key": "e3MIREz0UMEmrEBN8j1kSQOndlY95w7oF0LyKm8tsVGXJiuq",
        "customId": null,
        "url": "https://tgo15bqb07.ufs.sh/f/e3MIREz0UMEmrEBN8j1kSQOndlY95w7oF0LyKm8tsVGXJiuq",
        "size": 4336973,
        "uploadedAt": "2025-03-19T07:27:11.000Z"
      },
      {
        "name": "Jake_Smith_Manipulation,_Body_Language,_Dark_Psychology,_NLP,_Mind.pdf",
        "key": "e3MIREz0UMEm0DOyB0s9UbnqcJ2jFHyQIik6d8KXrp1vmTeD",
        "customId": null,
        "url": "https://tgo15bqb07.ufs.sh/f/e3MIREz0UMEm0DOyB0s9UbnqcJ2jFHyQIik6d8KXrp1vmTeD",
        "size": 1310540,
        "uploadedAt": "2025-03-19T07:27:11.000Z"
      },
      {
        "name": "the_road_-_text.pdf",
        "key": "e3MIREz0UMEmB5GkLZ4X5oup2LFE9gixAJeQRlNWYwfaq8Zm",
        "customId": null,
        "url": "https://tgo15bqb07.ufs.sh/f/e3MIREz0UMEmB5GkLZ4X5oup2LFE9gixAJeQRlNWYwfaq8Zm",
        "size": 401624,
        "uploadedAt": "2025-03-19T07:27:11.000Z"
      },
      {
        "name": "David_McRaney_You_Are_Not_So_Smart_Why_You_Have_Too_Many_Friends.pdf",
        "key": "e3MIREz0UMEmTN62X8asK5qUIxocnuWmHwfdC3RejG4ZyBJQ",
        "customId": null,
        "url": "https://tgo15bqb07.ufs.sh/f/e3MIREz0UMEmTN62X8asK5qUIxocnuWmHwfdC3RejG4ZyBJQ",
        "size": 1513741,
        "uploadedAt": "2025-03-19T07:27:11.000Z"
      },
      {
        "name": "Shane Parrish - Clear Thinking.pdf",
        "key": "e3MIREz0UMEmpx8eJu8FmBjQdvVPKc6R4ultWsIMapJeofOh",
        "customId": null,
        "url": "https://tgo15bqb07.ufs.sh/f/e3MIREz0UMEmpx8eJu8FmBjQdvVPKc6R4ultWsIMapJeofOh",
        "size": 6389931,
        "uploadedAt": "2025-03-19T07:27:11.000Z"
      },
      {
        "name": "ALongWalktoWaterbyParkLindaSue.pdf",
        "key": "e3MIREz0UMEm85etQD9VfQsmbdhl8CBU491OD3EHZXgNe0aF",
        "customId": null,
        "url": "https://tgo15bqb07.ufs.sh/f/e3MIREz0UMEm85etQD9VfQsmbdhl8CBU491OD3EHZXgNe0aF",
        "size": 1864217,
        "uploadedAt": "2025-03-19T07:27:11.000Z"
      },
      {
        "name": "The-Three-Musketeers.pdf",
        "key": "e3MIREz0UMEmFfoY22DHESWfmuyMNRhC3K8a4TBnt0DdLwQF",
        "customId": null,
        "url": "https://tgo15bqb07.ufs.sh/f/e3MIREz0UMEmFfoY22DHESWfmuyMNRhC3K8a4TBnt0DdLwQF",
        "size": 2434008,
        "uploadedAt": "2025-03-19T07:27:10.000Z"
      },
      {
        "name": "Mauri_Valtonen,_Hannu_Karttunen_The_three_body_problem_Cambridge.pdf",
        "key": "e3MIREz0UMEmpMY6AwFmBjQdvVPKc6R4ultWsIMapJeofOhD",
        "customId": null,
        "url": "https://tgo15bqb07.ufs.sh/f/e3MIREz0UMEmpMY6AwFmBjQdvVPKc6R4ultWsIMapJeofOhD",
        "size": 1786138,
        "uploadedAt": "2025-03-19T07:27:10.000Z"
      },
      {
        "name": "Gamini_Singla_HOW_I_TOPPED_THE_UPSC_AND_HOW_YOU_CAN_TOO_What_It.pdf",
        "key": "e3MIREz0UMEmb0qVBpM4suV6ZaX3cvFHzgtYBQpCGISwiDml",
        "customId": null,
        "url": "https://tgo15bqb07.ufs.sh/f/e3MIREz0UMEmb0qVBpM4suV6ZaX3cvFHzgtYBQpCGISwiDml",
        "size": 14272285,
        "uploadedAt": "2025-03-19T07:27:10.000Z"
      }
    ]';
    
    $books = json_decode($books_json, true);
    
    echo "Preparing to insert " . count($books) . " books.<br>";
    
    // Function to generate PDF thumbnail URL
    function generatePdfThumbnailUrl($pdfUrl, $title) {
        // Use PDF.js Express viewer as a thumbnail generator - this will show the first page
        $encodedUrl = urlencode($pdfUrl);
        return "https://documentservices.adobe.com/view-sdk/viewer.html?embedded=true&fileUrl={$encodedUrl}#page=1";
    }
    
    // Process each book
    foreach ($books as $index => $book) {
        // Extract title and author from filename
        $name = $book['name'];
        $parts = explode(' - ', str_replace('.pdf', '', $name));
        
        if (count($parts) > 1) {
            $author = $parts[0];
            $title = $parts[1];
        } else {
            // Handle filenames without the author - title format
            $cleanName = str_replace(['.pdf', '_'], ['', ' '], $name);
            $title = $cleanName;
            $author = "Unknown";
            
            // Try to extract better title and author
            if (strpos($name, ',') !== false) {
                $parts = explode(',', str_replace('.pdf', '', $name));
                $author = $parts[0];
                $title = isset($parts[1]) ? $parts[1] : $parts[0];
            }
        }
        
        // Set some books as trending or popular
        $is_trending = ($index < 5) ? 1 : 0;
        $is_popular = ($index % 3 == 0) ? 1 : 0;
        
        // Sample description
        $description = "This is a sample description for the book '$title' by $author. It provides insights and knowledge that readers will find valuable.";

        // Convert ISO date format to MySQL date format
        $uploaded_at = date('Y-m-d H:i:s', strtotime($book['uploadedAt']));
        
        // Generate thumbnail URL for the PDF
        $thumbnail_url = generatePdfThumbnailUrl($book['url'], $title);
        
        // Insert the book into the database
        $stmt = $conn->prepare("INSERT INTO books (title, author, file_key, file_url, file_size, uploaded_at, description, is_trending, is_popular) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssissii", 
            $title, 
            $author, 
            $book['key'], 
            $book['url'], 
            $book['size'], 
            $uploaded_at, 
            $description,
            $is_trending, 
            $is_popular
        );
        
        if (!$stmt->execute()) {
            echo "Error inserting book: " . $stmt->error . "<br>";
        } else {
            echo "Inserted book: " . $title . "<br>";
        }
        
        $stmt->close();
    }
    
    echo "Database setup completed successfully!";
    
    // Create admin user
    $admin_name = 'Tuba Mirza';
    $admin_email = 'tubamirza822@gmail.com';
    $admin_password = 'Tuba@12';
    $admin_hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    // Check if admin user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $admin_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Insert admin user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $admin_name, $admin_email, $admin_hashed_password);
        
        if ($stmt->execute()) {
            echo "<br>Admin user created successfully.";
        } else {
            echo "<br>Error creating admin user: " . $stmt->error;
        }
    } else {
        // Update existing user to be admin
        $user_id = $result->fetch_assoc()['id'];
        $stmt = $conn->prepare("UPDATE users SET name = ?, password = ?, is_admin = 1 WHERE id = ?");
        $stmt->bind_param("ssi", $admin_name, $admin_hashed_password, $user_id);
        
        if ($stmt->execute()) {
            echo "<br>Admin user updated successfully.";
        } else {
            echo "<br>Error updating admin user: " . $stmt->error;
        }
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    die("Connection error: " . $e->getMessage());
}

$conn->close();
?> 