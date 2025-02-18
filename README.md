[![Build](https://img.shields.io/badge/build-stable-green)](https://www.w2ort.com)
[![Donate](https://img.shields.io/badge/donate-Paypal-blue)](https://paypal.me/modernsnipe14?country.x=US&locale.x=en_US)
[![downloads](https://img.shields.io/github/downloads/modernsnipe14/blackflower/total)](https://cad.w2ort.com)


Black Flower CAD v1.13.2 - 12 AUG 2022



  Description:
  ------------
  Black Flower CAD is an open source Computer Aided Dispatch / Computer Aided Logging software package.  



  Contact Information:
  --------------------
  The current version of this software can be downloaded from this GitHub or hosted from https://cad.w2ort.com
  
  For bug reports, questions, etc, send email to:
    w2ort.matt@gmail.com

INSTALLATION
  
  Server Requirements:
  --------------------
  * MySQL database server v5.0 or greater
  * PHP version 7+, with MySQL integration.
  * Apache 2.0+ webserver running in Prefork mode, with PHP integration.
  
  * Linux (Debian/CentOS) recommended for mission critical installations.
  * Microsoft Windows (7/10) has also been used in development environments.
    -> IMPORTANT:  Production use on Microsoft Windows is NOT recommended,
       due to limitations of PHP running under Windows-based webservers and
       using Windows' Event Log emulation for syslog.
  
  
  Client Requirements:
  --------------------
  * 1024x768 (or greater) screen resolution required.
    -> 1280x1024 (or greater) resolution is recommended for best results.

  * Browser Support:
    -> Chrome: Working
    -> Microsoft Edge: Working
    -> Safari: Untested
    -> Firefox: Untested
    -> Internet Explorer is NOT supported, any other browsers not listed will not be supported.

  * Client system time must be synchronized with CAD server system time.
  
  * Microsoft Windows (7 or higher) is recommended for client operating system.
    -> Linux clients have also been tested successfully.

  
  
  Installation Quick Guide
  ------------------------

BEING WORKED ON
BEING WORKED ON
BEING WORKED ON




  To use the CAD system:
  ----------------------
  On the server, start or restart Apache and MySQL as needed.
  On the client, load your web browser and go to the CAD URL, e.g.:

    http://<server>/<path-to-CAD-application>/

  (or https:// if you have configured your system for HTTPS.)
  
  Log in with the CAD administrator username and password created 
  during step 4 above.  The password is case sensitive.
  
  Click on the "Settings" tab, then "Edit Users" in the Administration 
  dialog, and create the required usernames and passwords for CAD users.

  An access level less than 10 should be used for normal users.  
  Access levels of 10 or greater create a system administrator account.  

  Day to day use of CAD should be done through normal, rather than
  system administrator, accounts.
  
  
  Best Common Practices
  ---------------------
  1)  NTP
  
  Time synchronization is required for coordination of timestamps 
  generated on the server with those generated on the clients.  
  This can be done by running a standalone (stratum 1) NTP server 
  if on a completely isolated network, or by connecting all systems 
  to the Internet NTP network.  In the Internet case it is 
  recommended to run a caching NTP server on the CAD server, and 
  synchronize all clients to that.  A popular freeware Windows NTP 
  client is available from http://www.oneguycoding.com/automachron/.
  
  2)  Syslog
  
  CAD will use the native system logging functionality on UNIX or 
  Windows systems.  On UNIX systems, CAD uses the local4 logging level,
  and obeys the severity filter as set in cad.conf.  On Windows, 
  *all* messages are logged to the Application section of the Event 
  Viewer, disregarding the severity filter as set in cad.conf.  
  
  3)  Network security
  
  Any network running CAD or other such systems should be kept 
  isolated from insecure network traffic such as the Internet.  
  The CAD server should be reachable from the CAD clients but they
  should not be reachable from untrusted (outside/Internet) systems.
  Any consumer-grade or better hardware firewall is sufficient for 
  purposes of CAD network security.  
  
  In an extremely small or field-deployable CAD installation, it is 
  possible to install the server on a system which will also be one
  of the clients of the installation.  If that is the only system 
  expected to use CAD, a software firewall on that host is sufficient 
  if configured correctly.  Software firewalls are also generally 
  recommended for any Microsoft Windows client or server systems 
  as best practice.  CAD is a cleanly client-server system, it does 
  not require network ingress access for connections _to_ clients.
  
  Ports required to be accessible on CAD server by the CAD clients
  for application communication:
  
    80 (http)   - Normal operation: HTTP on a secure network.
  *or*
   443 (https)  - If using secure HTTPS on an insecure network
  
  Other ports that may be used in a full network environment:
  
    53 (domain) - If using the CAD server as a DNS server.
    67 (bootps) - If using the CAD server as a DHCP server.
   123 (ntp)    - If using the CAD server as an NTP server.
  
  
  
