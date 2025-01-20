#!/bin/bash
clear

echo -e "                 \033[34m+++++++++++++++++++\033[96mx≈≈≈≈≈\033[0m"
echo -e "                \033[34m+++++++++++++++++++\033[96m÷≈≈≈≈≈\033[0m"
echo -e "               \033[34m+++++              \033[96m≈≈≈≈≈≈\033[0m"
echo -e "              \033[34m+++++              \033[96m≈≈≈≈≈\033[0m"
echo -e "             \033[34m+++++              \033[96m≈≈≈≈≈\033[0m"
echo -e "            \033[34m+++++              \033[96m≈≈≈≈≈\033[0m"
echo -e "           \033[34m+++++\033[96m-≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈\033[0m"
echo -e "          \033[34m+++++\033[96m≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈\033[0m"
echo -e "         \033[34m+++++\033[96m≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈≈\033[0m"
echo -e "          \033[34m+++++     \033[36m×××××××××××××××××××××\033[0m"
echo -e "           \033[34m+++++   \033[36m×××××××××××××××××××××××\033[0m"
echo -e "            \033[34m+++++ \033[36m×××××               ×××××\033[0m"
echo -e "             \033[34m+++++\033[36m××××              +++××××××\033[0m"
echo -e "              \033[34m+++++-×              \033[36m++++x××××××\033[0m"
echo -e "               \033[34m++++++++++++++++++++++  \033[36mx××××÷\033[0m"
echo -e "                \033[34m+++++++++++++++++++++     \033[36mx××××\033[0m"
echo -e "                 \033[34m+++++++++++++++++++       \033[36m÷÷x××\033[0m"
echo ""
echo ""
echo -e "  \033[36m ██████╗ ██████╗ ███████╗███╗   ██╗ ██████╗ ██████╗  ██████╗"
echo -e "  ██╔═══██╗██╔══██╗██╔════╝████╗  ██║██╔════╝ ██╔══██╗██╔════╝"
echo -e "  ██║   ██║██████╔╝█████╗  ██╔██╗ ██║██║  ███╗██████╔╝██║"
echo -e "  ██║   ██║██╔═══╝ ██╔══╝  ██║╚██╗██║██║   ██║██╔══██╗██║"
echo -e "  ╚██████╔╝██║     ███████╗██║ ╚████║╚██████╔╝██║  ██║╚██████╗"
echo -e "   ╚═════╝ ╚═╝     ╚══════╝╚═╝  ╚═══╝ ╚═════╝ ╚═╝  ╚═╝ ╚═════╝\033[0m"


echo ""
echo -e "################################################################"
echo -e "##                  WELCOME TO OPENGRC                        ##"
echo -e "################################################################"
echo ""
echo -e "\033[5m\033[31mWarning: This installer will overwrite your current install. If"
echo -e "you are not sure that's what you want to do, stop now!\033[0m"

echo ""
read -p "Press any key to Continue, or Ctrl+C to quit " choice

echo ""
echo -e "################################################################"
echo ""

# Check Node.js version
node_version=$(node -v | cut -c 2-)
if [[ "$node_version" < "16" ]]; then
  echo "Checking Node.js version... FAILED! Node.js version 16 or higher is required. You have $node_version"
  exit 1
else
  echo -e "Checking Node.js version... \033[32mGOOD!\033[0m"
fi


# Check NPM version
npm_version=$(npm -v)
required_version="9"
if [[ "$(printf '%s\n' "$required_version" "$npm_version" | sort -V | head -n 1)" != "$required_version" ]]; then
  echo "Checking NPM version... FAILED! NPM version 9 or higher is required. You have $npm_version"
  exit 1
else
  echo -e "Checking NPM version... \033[32mGOOD!\033[0m"
fi

#Copy default env file
cp .env.example .env

# Run Composer
echo "Installing Composer Dependencies..."
  composer update


# Generate application key
echo "Generating application key..."
  php artisan key:generate


# Database setup
echo "Available database backends:"
select db_choice in sqlite mysql postgres; do
  case $db_choice in
    sqlite)
      sed -i'' -e "s/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/" .env
      sed -i'' -e "s#^DB_DATABASE=.*#DB_DATABASE=$(pwd)/database/opengrc.sqlite#" .env
      rm -f database/opengrc.sqlite
      break
      ;;
    mysql|postgres)
      read -p "Enter database host [127.0.0.1]: " db_host
      db_host=${db_host:-127.0.0.1}
      read -p "Enter database port [3306]: " db_port
      db_port=${db_port:-3306}
      read -p "Enter database name [opengrc]: " db_name
      db_name=${db_name:-opengrc}
      read -p "Enter database username [root]: " db_user
      db_user=${db_user:-root}
      read -s -p "Enter database password: " db_password
      echo
      sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=$db_choice/" .env
      sed -i "s/^DB_HOST=.*/DB_HOST=$db_host/" .env
      sed -i "s/^DB_PORT=.*/DB_PORT=$db_port/" .env
      sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$db_name/" .env
      sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$db_user/" .env
      sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$db_password/" .env
      break
      ;;
    *)
      echo "Invalid choice."
      ;;
  esac
done
echo "Updated .env file..."



# Run migrations
echo "Installing database tables..."
php artisan migrate


# Create admin user
read -p "Admin Username [admin@example.com]: " admin_username
admin_username=${admin_username:-admin@example.com}
read -s -p "Admin Password: " admin_password
admin_password=${admin_password:-password}
echo
php artisan opengrc:create-user "$admin_username" "$admin_password"

# Run seeders
echo "Setting up custom configurations..."
php artisan db:seed --class=SettingsSeeder
php artisan db:seed --class=RolePermissionSeeder

# Set general settings
read -p "Site Name [OpenGRC]: " site_name
site_name=${site_name:-OpenGRC}
read -p "Site URL [https://opengrc.test]: " site_url
site_url=${site_url:-https://opengrc.test}

php artisan settings:set general.url $site_url
php artisan settings:set general.name $site_name

# Update .env with site name and URL (using a different delimiter for sed)
sed -i "s|APPNAME=.*|APPNAME=$site_name|g" .env
sed -i "s|APP_URL=.*|APP_URL=$site_url|g" .env

# Build front-end assets
echo "Building Front-End Assets..."
npm install && npm run build

# Set Permissions
echo "Setting Starter Permissions"
find . -type f -print0 | xargs --null sudo chmod 666
find . -type d -print0 | xargs --null sudo chmod 775
sudo chmod 777 set_permissions
sudo chmod 777 artisan
sudo chmod 777 install.sh
sudo chmod 777 vendor/bin/*
sudo chmod 777 storage -R
sudo chmod 777 database
sudo chmod 777 database/opengrc.sqlite
sudo chmod 777 node_modules/.bin/*

echo -e "\033[5m\033[31m** IMPORTANT **\033[0m"
echo "Change the file system permissions for least privilege based on you own system."

echo -e "################################################################"
echo -e "## OpenGRC INSTALLED! Visit $site_url to login!"
echo -e "################################################################"