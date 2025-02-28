const form = document.getElementById('contactForm');
const contactFormResult = document.getElementById('contactFormResult');

form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);
    const object = Object.fromEntries(formData);
    const json = JSON.stringify(object);
    contactFormResult.innerHTML = 'Invio della e-mail in corso...';
    contactFormResult.style.color = 'black';
    contactFormResult.style.display = 'block';

    fetch('send_mail.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: json
        })
        .then(async (response) => {
            if (response.status == 200) {
                contactFormResult.innerHTML = 'E-mail inviata correttamente';
                contactFormResult.style.color = 'green';
                contactFormResult.style.display = "block";
            }
        })
        .catch(error => {
            contactFormResult.innerHTML = "Errore durante l'invio della email";
            contactFormResult.style.color = 'red';
            contactFormResult.style.display = 'block';
            console.log(error);
        })
        .then(function() {
            form.reset();
            setTimeout(() => {
                contactFormResult.style.display = 'none';
            }, 5000);
        });
});