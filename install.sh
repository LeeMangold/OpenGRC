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

# Check PHP version
php_version=$(php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;")
required_php="8.4"
if [[ "$(printf '%s\n' "$required_php" "$php_version" | sort -V | head -n 1)" != "$required_php" ]]; then
  echo "Checking PHP version... FAILED! PHP version 8.4 or higher is required. You have $php_version"
  exit 1
else
  echo -e "Checking PHP version... \033[32mGOOD!\033[0m"
fi

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

## Run Composer
echo "Installing Composer Dependencies..."
  composer update

php artisan opengrc:install
