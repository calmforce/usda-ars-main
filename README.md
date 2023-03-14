# Deploy USDA ARS Drupal project using Docker on Windows  

**Works for: Windows Server 2019, Windows 10**  

`$SITES_DIR` = A directory accessible to Docker that shall contain our web application directory  

### Install Docker Desktop for Windows  
Follow Docker Hub installation instructions to install Docker Desktop for Windows (latest stable version):  
https://hub.docker.com/editions/community/docker-ce-desktop-windows/  
After install:  
Under `Settings` > `General` uncheck `Use the WSL 2 based engine`  
Under `Settings` > `Resources` > `File Sharing` : add `$SITES_DIR` directory as a resource  


### Enable WSL  
Open PowerShell as Administrator and run:  
`Enable-WindowsOptionalFeature -Online -FeatureName Microsoft-Windows-Subsystem-Linux`  


### Install WSL Ubuntu 20.04 Distro  
reference:  
https://docs.microsoft.com/en-us/windows/wsl/install-on-server  

Download distro from:  
https://docs.microsoft.com/en-us/windows/wsl/install-manual  
Ubuntu 20.04 download link:  
https://aka.ms/wslubuntu2004  

Download, then rename `Ubuntu_2004.2020.424.0_x64.appx` to `Ubuntu_2004.2020.424.0_x64.zip`  
Use 7zip to unpack, then from within the unpacked folder execute:  
`ubuntu2004.exe`  


### Configure WSL Ubuntu 20.04  
**Command Line (in WSL Ubuntu 20.04):**  
`sudo apt install -y php-cli php-dom php-gd php-curl unzip make`  
`curl -sS https://getcomposer.org/installer -o composer-setup.php`  
`sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer`  
`composer self-update`  


### USDA ARS Drupal project  
**Command Line (in WSL Ubuntu 20.04):**  
`cd $SITES_DIR`  
`git clone https://bitbucket.org/usdawcmaas/usda-ars-drupal.git`  
`cd usda-ars-drupal`  
`composer install`  


### Run Docker  
**Command Line (Windows PowerShell):**  
`cd $SITES_DIR`  
`cd usda-ars-drupal`  

Create and start the Docker containers.  Either run this docker-compose command:  
`docker-compose up -d`  
Or install the make utility for Windows: http://gnuwin32.sourceforge.net/packages/make.htm  
and run the following command which is defined in the Makefile in the root of the project:  
`make up`  

Navigate to `http://127.0.0.1:8888/` in your web browser.  You should see the Drupal site install page.  Proceed with install, select `Lightning` profile.  For DB credentials, enter the same credentials as contained in the .env file  
