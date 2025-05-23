sudo mkdir -p /var/www/html/plugins
sudo cp -r ~/raspap-plugin-matrix/MatrixPlugin /var/www/html/plugins
sudo cp ~/raspap-plugin-matrix/files/sudoers/091_raspap_matrix /etc/sudoers.d/
rm -rf raspap-plugin-matrix