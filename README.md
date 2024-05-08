# QuickDrop 📁

Welcome to QuickDrop – the simplest way to share files with style! QuickDrop is your go-to solution for effortless file sharing, designed to be sleek, fast, and oh-so-easy to use. Whether you're sending files to your team or receiving them from clients, QuickDrop has got you covered.

🚀 **Features:**
- **Sleek Interface:** Say goodbye to clunky interfaces! QuickDrop's design makes file sharing a breeze.
- **Speedy Transfers:** Fast and efficient, because who has time to wait?
- **Cross-Platform Compatibility:** Access QuickDrop from anywhere, on any device.

## Installation 🛠️

### Prerequisites:
- PHP >= 8.2
- Composer
- npm
- MySQL, PostgreSQL, SQLite, or SQL Server

### Installation Steps:
1. **Clone the Repository:**
   ```bash
   git clone https://github.com/pondi/quickdrop.git
   ```

2. **Navigate to the Project Directory:**
   ```bash
   cd quickdrop
   ```

3. **Install Dependencies:**
   ```bash
   composer install
   npm install && npm run dev
   ```

4. **Set Up Environment Variables:**
   - Rename `.env.example` to `.env` and configure it with your database credentials and app URL.

5. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

6. **Run Migrations and Seeders:**
   ```bash
   php artisan migrate --seed
   ```

7. **Serve the Application:**
   ```bash
   php artisan serve
   ```

8. **Access QuickDrop:**  
   Visit `http://localhost:8000` in your web browser.

## Deployment 🚀

### Web Server:
Deploy QuickDrop on your preferred web server using standard Laravel deployment procedures.

### Docker Container:
Deploy QuickDrop with Docker from Docker Hub:

```bash
docker pull pondi/quickdrop
docker run -d -p 80:80 pondi/quickdrop
```

## Contributing 🤝

We love contributions! If you have ideas for improvements or encounter any issues, please share them on GitHub.

## License 📜

This project is licensed under the Apache-2.0 License. See the [LICENSE](LICENSE) file for details.

---

QuickDrop - Sharing Made Simple.
