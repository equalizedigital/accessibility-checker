#Setup port mapping so server make loopback calls.
npx wp-env run wordpress sudo apt install iptables -y
npx wp-env run wordpress sudo iptables -t nat -A OUTPUT -o lo -p tcp --dport 8888 -j REDIRECT --to-port 80
