## CSV to JSON Converter

This is a **Laravel web application** for converting CSV files to JSON format with the ability to view and manage uploaded files.

### Main functionality:

1. **CSV file upload** - users can upload CSV files through the web interface
2. **Automatic conversion** - uploaded files are automatically processed in the background and converted to JSON
3. **File viewing** - display a list of all uploaded files with their processing status
4. **JSON download** - ability to download converted JSON files
5. **Authorization system** - users must log in to work with files

### Technical features:

- **Backend**: Laravel 12 with PHP 8.4
- **Frontend**: Vue.js with Tailwind CSS
- **File processing**: Background jobs for asynchronous CSV processing
- **Storage**: Files are stored in Laravel Storage
- **Database**: PostgreSQL for storing file metadata
- **Containerization**: Docker for deployment

### Data structure:

The application creates a hierarchical JSON structure from flat CSV data, where each CSV level becomes a branch in the JSON tree, and the last level becomes a leaf node.

The project demonstrates modern Laravel development practices using queues, service layer, repositories, and SPA architecture.
### Clone the Repository

```bash
git clone https://github.com/russel-bg/webmasters_demo_csv_converter.git
cd webmasters_demo_csv_converter
```

### Setting Up the Development Environment

1. Copy the .env.example file to .env and adjust any necessary environment variables:

```bash
cp .env.example .env
```

Hint: adjust the `UID` and `GID` variables in the `.env` file to match your user ID and group ID. You can find these by running `id -u` and `id -g` in the terminal.

2. Start the Docker Compose Services:

```bash
docker compose -f compose.dev.yaml up -d
```

3. Install Laravel Dependencies:

```bash
docker compose -f compose.dev.yaml exec workspace bash
composer install
php artisan key:generate

npm install
npm run dev
```

4. Run Migrations and seeders:

```bash
docker compose -f compose.dev.yaml exec workspace php artisan migrate:fresh --seed
```



5. Run queues (#TODO add laravel horizon):

```bash
docker compose -f compose.dev.yaml exec workspace php artisan queue:work
```


6. Access the Application:

Open your browser and navigate to [http://localhost](http://localhost).

