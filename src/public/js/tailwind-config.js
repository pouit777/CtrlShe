// public/js/tailwind-config.js
tailwind.config = {
    theme: {
        extend: {
            colors: {
                //bleu très foncé (textes importants, titres, ou navbar)
                primary: '#27187E',
                
                // bleu électrique / moderne (boutons principaux, liens actifs)
                secondary: '#758BFD',
                
                // bleu doux / pastel (badges, fonds de composants, bordures)
                lightBlue: '#AEB8FE',
                
                // blanc / gris très clair (Idéal pour le fond du site ou des cartes en mode clair)
                surface: '#F1F2F6',
                
                // orange vif (le bouton de validation, les alertes, les scores)
                accent: {
                    DEFAULT: '#FF8600',
                    hover: '#e67800' // version légèrement plus sombre automatique pour le survol
                }
            }
        }
    }
}