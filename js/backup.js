    $(function() {
        $(".data-backup-now").click(function() {
            $(".backup-sated").remove();
            setTimeout(function() {
                checkbase();
            }, 1500);
            $("#backup-state").append("<div style='border-color: #ffba00;' class='updated notice backup-sated'><p><img style='width: 16px;margin-bottom: -3px;' src='/wp-admin/images/spinner.gif' />&nbsp;&nbsp;Dein Backup wird im Hintergrund verarbeitet. Du kannst zu einer anderen Seite wechseln. Den Status des Backups siehst du unter <a href='admin.php?page=backup_restore'>Wiederherstellen</a>.</p></div>");
            var inc = 'data';
            $.ajax({url: "/wp-content/plugins/backup/do-backup.php", type: "POST", data: { inc: inc }, success: function(result){
                if(result == "once-error") {
                    $(".backup-sated").addClass("error").removeClass("updated");
                    $(".backup-sated").html("<p>Bitte warte, bis der zuletzt gestartete Backupprozess beendet wurde. <a href='admin.php?page=backup_restore'>Klicke hier</a> um alle anzuzeigen.</p>");
                } else {
                    setTimeout(function() {
                        checkbase(0);
                    }, 2500);

                    $(".backup-sated").html(result + "<p>Dein Backup wurde erfolgreich erstellt. <a href='admin.php?page=backup_restore'>Klicke hier</a> um alle anzuzeigen.</p>");
                }
            }});
        });
        $(".full-backup-now").click(function() {
            $(".backup-sated").remove();
            setTimeout(function() {
                checkbase();
            }, 2500);
            $("#backup-state").append("<div style='border-color: #ffba00;' class='updated notice backup-sated'><p><img style='width: 16px;margin-bottom: -3px;' src='/wp-admin/images/spinner.gif' />&nbsp;&nbsp;Dein Backup wird im Hintergrund verarbeitet. Du kannst zu einer anderen Seite wechseln. Den Status des Backups siehst du unter <a href='admin.php?page=backup_restore'>Wiederherstellen</a>.</p></div>");
            var inc = 'full';
            $.ajax({url: "/wp-content/plugins/backup/do-backup.php", type: "POST", data: { inc: inc }, success: function(result){
                if(result == "once-error") {
                    $(".backup-sated").addClass("error").removeClass("updated");
                    $(".backup-sated").html("<p>Bitte warte, bis der zuletzt gestartete Backupprozess beendet wurde. <a href='admin.php?page=backup_restore'>Klicke hier</a> um alle anzuzeigen.</p>");
                } else {
                    setTimeout(function() {
                        checkbase(1);
                    }, 2500);

                    $(".backup-sated").html(result + "<p>Dein Backup wurde erfolgreich erstellt. <a href='admin.php?page=backup_restore'>Klicke hier</a> um alle anzuzeigen.</p>");
                }
            }});
        });
        $(".database-backup-now").click(function() {
            $(".backup-sated").remove();
            setTimeout(function() {
                checkbase();
            }, 1500);
            $("#backup-state").append("<div style='border-color: #ffba00;' class='updated notice backup-sated'><p><img style='width: 16px;margin-bottom: -3px;' src='/wp-admin/images/spinner.gif' />&nbsp;&nbsp;Dein Backup wird im Hintergrund verarbeitet. Du kannst zu einer anderen Seite wechseln. Den Status des Backups siehst du unter <a href='admin.php?page=backup_restore'>Wiederherstellen</a>.</p></div>");
            var inc = 'database';
            $.ajax({url: "/wp-content/plugins/backup/do-backup.php", type: "POST", data: { inc: inc }, success: function(result){
                if(result == "once-error") {
                    $(".backup-sated").addClass("error").removeClass("updated");
                    $(".backup-sated").html("<p>Bitte warte, bis der zuletzt gestartete Backupprozess beendet wurde. <a href='admin.php?page=backup_restore'>Klicke hier</a> um alle anzuzeigen.</p>");
                } else {
                    setTimeout(function() {
                        checkbase(0);
                    }, 1500);

                    $(".backup-sated").html(result + "<p>Dein Backup wurde erfolgreich erstellt. <a href='admin.php?page=backup_restore'>Klicke hier</a> um alle anzuzeigen.</p>");
                }
            }});
        });
        $(".mediafiles-backup-now").click(function() {
            $(".backup-sated").remove();
            setTimeout(function() {
                checkbase();
            }, 1500);
            $("#backup-state").append("<div style='border-color: #ffba00;' class='updated notice backup-sated'><p><img style='width: 16px;margin-bottom: -3px;' src='/wp-admin/images/spinner.gif' />&nbsp;&nbsp;Dein Backup wird im Hintergrund verarbeitet. Du kannst zu einer anderen Seite wechseln. Den Status des Backups siehst du unter <a href='admin.php?page=backup_restore'>Wiederherstellen</a>.</p></div>");
            var inc = 'mediafiles';
            $.ajax({url: "/wp-content/plugins/backup/do-backup.php", type: "POST", data: { inc: inc }, success: function(result){
                if(result == "once-error") {
                    $(".backup-sated").addClass("error").removeClass("updated");
                    $(".backup-sated").html("<p>Bitte warte, bis der zuletzt gestartete Backupprozess beendet wurde. <a href='admin.php?page=backup_restore'>Klicke hier</a> um alle anzuzeigen.</p>");
                } else {
                    setTimeout(function() {
                        checkbase(0);
                    }, 1500);

                    $(".backup-sated").html(result + "<p>Dein Backup wurde erfolgreich erstellt. <a href='admin.php?page=backup_restore'>Klicke hier</a> um alle anzuzeigen.</p>");
                }
            }});
        });
    });