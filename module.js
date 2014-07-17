
M.report_deadlines = {
    Y : null,
    transaction : [],

	init: function (Y) {
        var select = Y.one('#showpastchk');
        select.on('change', function (e) {
        	var showpast = e.target.get('checked');
        	window.location = M.cfg.wwwroot + "/report/deadlines/index.php?showpast=" + showpast
        });
    }
}