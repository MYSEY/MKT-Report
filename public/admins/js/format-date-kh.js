 // function format date to language KH
 Date.prototype.getAmPm = function () {
    if( this.getHours() >= 12 ) { return 1 }; // pm
    return 0; // am
}
var locale = {
    en: {
        month: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September ', 'October', 'November', 'December'],
        ampm: [ 'am', 'pm' ]
    },
    km: {
        month: ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 
                'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'
            ],
        ampm: [ 'ព្រឹក', 'ល្ងាច' ]
    }
};

var toLocaleNumber = function( num, lang, zeroPadding ) {
    if( typeof num !== 'number' ) return null;

    var numInteger = parseInt( num );
    var numString = numInteger.toString();
    
    if( zeroPadding > 0 && numString.length < zeroPadding ) {
        numString = '0' + numString;
    }

    // support only khmer
    if( lang !== 'km' ) { return numString };

    var khmerNumber = '';
    var numbersKhmer = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'];

    for( var i=0; i < numString.length; i++ ) {
        khmerNumber += numbersKhmer[parseInt(numString[i])];
    }

    return khmerNumber;
};

var formatDate = function( date, lang, format_date ) {
    var formattedDate = null;
    var hours = date.getHours();
    if( hours > 12 ) { hours -= 12; }; 

     if (format_date) { 
        if(format_date.day && !format_date.month && !format_date.year) {
            formattedDate = toLocaleNumber( date.getDate(), lang, 2 )
        }else if (format_date.month && !format_date.day && !format_date.year) {
            formattedDate = locale[lang]['month'][date.getMonth()]
        }else if (format_date.year && !format_date.day && !format_date.month) {
            formattedDate = toLocaleNumber( date.getFullYear(), lang )
        }else if (format_date.time) {
            formattedDate = toLocaleNumber( hours, lang, 2 )
                            + ':' + toLocaleNumber( date.getMinutes(), lang, 2 )
                            +' ' + locale[lang]['ampm'][date.getAmPm()];
        }
        if (format_date.day && format_date.month) {
            formattedDate = toLocaleNumber( date.getDate(), lang, 2 )
                            + '-' + locale[lang]['month'][date.getMonth()]
        }
        if (format_date.month && format_date.year) {
            formattedDate = locale[lang]['month'][date.getMonth()]
                            + '-' + toLocaleNumber( date.getFullYear(), lang )
        }

    }else{
        formattedDate = toLocaleNumber( date.getDate(), lang, 2 )
        + '-'
        + locale[lang]['month'][date.getMonth()]
        + '-'
        + toLocaleNumber( date.getFullYear(), lang )
        + ' '
        + toLocaleNumber( hours, lang, 2 )
        + ':'
        + toLocaleNumber( date.getMinutes(), lang, 2 );
        + ' '
        + locale[lang]['ampm'][date.getAmPm()];
    }
    return formattedDate;
};