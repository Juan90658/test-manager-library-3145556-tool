FROM php:8.3-cli
WORKDIR /tool
COPY src ./src
CMD ["php", "src/seed.php"]
