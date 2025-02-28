// hamburger
document
    .querySelector('.hamburger')
    .addEventListener(
        'click',
        function() {
            document
                .querySelector('.navigation-menu')
                .classList
                .toggle('active');
        });
        