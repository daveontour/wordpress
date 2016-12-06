cd C:/Users/david.burton/Documents/GitHub/wordpress/wp-content/plugins/qrm/includes/qrmmainapp/js
call uglifyjs services.js --compress --munge -o services.min.js
call uglifyjs directives.js --compress --munge -o directives.min.js
call uglifyjs qrm-common.js services.js app.js config.js directives.js controllers.js --compress --munge -o qrm.min.js
