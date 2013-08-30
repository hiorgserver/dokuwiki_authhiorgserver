dokuwiki_authhiorgserver
========================

Plugin for DokuWiki: Single-Sign-On mit HiOrg-Server (http://www.hiorg-server.de)

Anleitung zur Installation
--------------------------

0) Erstellen Sie vorab (per FTP) eine Sicherungskopie der Dokuwiki-Einstellungen
  .../conf/local.php

1) Installieren Sie das Plugin über den Plugin-Manager, Quell-URL: 
  https://github.com/hiorgserver/dokuwiki_authhiorgserver/archive/master.zip

2) Setzen Sie zuerst die Einstellungen des Plugins in Ihrem Wiki unter
  Admin -> Konfiguration -> Plugins: Auth: SSO mit HiOrg-Server
  ACHTUNG: lassen Sie zunächst den "Authentifizierungsmechanismus" (weiter oben, Abschnitt "Authentifizierungs-Konfig") noch auf der vorherigen Einstellung bestehen!

  Geben Sie im Feld "Organisationskürzel" das Org.-Kürzel Ihres HiOrg-Server ein (3-4 Kleinbuchstaben). Tragen Sie im Feld "Admins" Ihren Benutzernamen beim HiOrg-Server ein.

3) Klicken Sie jetzt ganz unten auf [Speichern], um die Einstellungen des Plugins zu sichern.

4) Stellen Sie JETZT ERST im Abschnitt "Authentifizierungs-Konfig - Authentifizierungsmechanismus (authtype) um auf "authhiorgserver" und klicken erneut auf [Speichern].


All documentation for this plugin can be found at
https://github.com/hiorgserver/dokuwiki_authhiorgserver

If you install this plugin manually, make sure it is installed in
lib/plugins/authhiorgserver/ - if the folder is called different it
will not work!

Please refer to http://www.dokuwiki.org/plugins for additional info
on how to install plugins in DokuWiki.

----
Copyright (C) HiOrg Server GmbH <support@hiorg-server.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See the COPYING file in your DokuWiki folder for details
