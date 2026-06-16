// public/js/tailwind-config.js
tailwind.config = {
    theme: {
        extend: {
            colors: {
                //bleu très foncé (textes importants, titres, ou navbar)
                primary: {
                    DEFAULT: '#27187E',
                    hover: '#211467' // version légèrement plus sombre automatique pour le survol
                },
                
                // bleu électrique / moderne (boutons principaux, liens actifs)
                secondary: {
                    DEFAULT: '#758BFD',
                    hover: '#5e71cc' // version légèrement plus sombre automatique pour le survol
                },
                
                // bleu doux / pastel (badges, fonds de composants, bordures)
                lightBlue: {
                    DEFAULT: '#AEB8FE',
                    hover: '#8c93c8' // version légèrement plus sombre automatique pour le survol
                },
                
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