# Code Sign App

This is an official repository for the Code Sign App, a tool designed to help developers manage their licensing and code signing processes more efficiently.
The Code Sign App provides a user-friendly interface that allows developers to easily create and assign licenses.

## Features
- **License Management**: Create, assign, and manage licenses for your software projects.
- **Stripe Integration**: Seamlessly integrate with Stripe for payment processing and subscription management.
- **User-Friendly Interface**: Intuitive design that simplifies the code signing and licensing workflow.
- **Activation System**: Platform allows customer to activate their license only on specific number of devices, ensuring compliance with licensing terms.

## Getting Started

To get started with the Code Sign Controller, follow these steps:

```bash
# 1. Copy the repository to your local machine
git clone https://github.com/Code-Sign-Labs/app.git
cd controller

# 2. Copy the example .env file
cp .env.example .env

# 3. (Optionally) Adjust environment variables
nano .env

# 4. Run the containers
docker compose up -d --build

# 5. Install PHP dependencies
docker compose exec php composer install

# 6. Check if it works
curl http://localhost:8080/
```

## List of things to do

Check out our [Trello](https://trello.com/b/ign4SDWN/code-sign) and see the list of things to do. If you want to contribute, please reach out to us.


## Roadmap

- [ ] Finish the basic version of Code Sign App
- [ ] Publish Code Sign as docker image to Docker Hub