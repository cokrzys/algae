# Get algae

Procedure below allows Apache to follow a symbolic link to the algae web interface.  Installing algae in another location or even directly in the web root is possible, but managing permissions can be a challenge and there are potential security risks.  This process also allows you to quickly download and install an updated version and maintain an older version by changing the symbolic link.

```shell
# change to location to install 3rd party apps
cd /opt

# download the latest algae
sudo wget https://github.com/cokrzys/algae/archive/refs/heads/main.zip

# if not already installed
sudo apt-get install zip

# unzip to a unique folder to support multiple versions
sudo unzip main.zip -d algae20260807

# remove zip file
sudo rm main.zip
```

Note the location of the algae ```web``` folder, for example from above:

```shell
/opt/algae20260809/algae-main/web
```
