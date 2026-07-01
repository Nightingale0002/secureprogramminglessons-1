# Secure Programming Lessons

Deze repository bevat een educatieve webapplicatie voor webbeveiliging met een eenvoudige bank-achtige flow.

## Wat is aangepast?
- SQL-injectie is opgelost via voorbereide statements.
- Wachtwoorden worden gehashd met PASSWORD_DEFAULT.
- XSS-achtige output is afgevangen met htmlspecialchars.
- Brute-force blokkering is toegevoegd voor het inlogformulier.
- Transacties en toegang tot transacties zijn beter gecontroleerd.

## Starten met Docker
1. Zorg ervoor dat Docker op je systeem is geïnstalleerd.
2. Open een terminal in de projectmap.
3. Start de containers met: docker compose up --build
4. Open de applicatie op http://localhost:8000
5. phpMyAdmin is beschikbaar op http://localhost:8080