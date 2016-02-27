App = {
    utils: {
        timer: function(diff) {
            days  = Math.floor( diff / (60*60*24) );
            hours = Math.floor( diff / (60*60) );
            mins  = Math.floor( diff / (60) );
            secs  = Math.floor( diff );

            dd = days;
            hh = hours - days  * 24;
            mm = mins  - hours * 60;
            ss = secs  - mins  * 60;

            var result = [];

            if( hh > 0) result.push(hh ? this.addzero(hh) : '00');
            result.push(mm ? this.addzero(mm) : '00');
            result.push(ss ? this.addzero(ss) : '00');

            return result.join(':');
        },

        addzero: function(n) {
            return (n < 10) ? '0'+n : n;
        },
    },
};