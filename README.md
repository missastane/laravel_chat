# Laravel Chat Project

A real-time chat system built with Laravel, Pusher, JWT authentication, and MySQL.

## Installation

1. Clone the repository.
2. Copy `.env.example` to `.env` and set your local environment variables.
3. Run `composer install`
4. Run `php artisan key:generate`
5. Run `php artisan jwt:secret`
6. Configure your database.
7. Run `php artisan migrate`
8. Run `php artisan serve`

## Features

- Private and group conversations
- Realtime messaging using Pusher
- JWT-based authentication
- Message delivery & read status
- Join requests for groups

## License

MIT
