<?php

$moduleFields['appdKFUpk2X2Ok8Dc'] = array (
    'CONTACTS' => array(
        'ID___COMET'=> array( 'label' => 'ID Comet', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'relate' => false),
        'STATUS' => array( 'label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'relate' => false),
        'NOM' => array( 'label' => 'Nom', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'PRENOM' => array( 'label' => 'Prénom', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'EMAIL'=> array( 'label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'MOBILE'=> array( 'label' => 'Mobile', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'REFERENT DE'=> array( 'label' => 'Référent de', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'DATE___DE___NAISSANCE'=> array( 'label' => 'Date de naissance', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'Civilité'=> array( 'label' => 'Civilité', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'pole'=> array( 'label' => 'Pôle', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'delete'=> array( 'label' => 'Deleted', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
    ),
    'BINOMES' => array(
        'ID___COMET'=> array( 'label' => 'Id Comet', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'relate' => false),
        'NAME' => array( 'label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'MISE___EN___PLACE' => array( 'label' => 'Mise en place', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'FIN' => array( 'label' => 'Fin', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'precision_lieu_c' => array( 'label' => 'Precision lieu', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'heure_babituelle_rencontre_c' => array( 'label' => 'heure_babituelle_rencontre_c', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'lieu_habituel_rencontre_c' => array( 'label' => 'lieu_habituel_rencontre_c', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'statut_c' => array( 'label' => 'statut_c', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'jour_habituel_rencontre_c' => array( 'label' => 'jour_habituel_rencontre_c', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'NOM___EQUIPE' => array( 'label' => 'NOM EQUIPE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'pole' => array( 'label' => 'pole', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'annee_scolaire_c' => array( 'label' => 'annee_scolaire_c', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'TYPE___BOT___COMET' => array( 'label' => 'TYPE BOT COMET', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'delete'=> array( 'label' => 'Delete', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
		'BENEFICIAIRE'=> array( 'label' => 'Bénéficiaire', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true), 
        'BENEVOLES'=> array( 'label' => 'Bénévoles', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true), 
        'Reponse'=> array( 'label' => 'Reponse', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true), 
        'REFERENT'=> array( 'label' => 'REFERENT', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true), 
        'POLES'=> array( 'label' => 'POLES', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true), 
    ),
    'POLE' => array(
        'prenom'=> array( 'label' => 'Prénom', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'nom'=> array( 'label' => 'Nom', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'mail'=> array( 'label' => 'Mail', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'numéro'=> array( 'label' => 'Numéro', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'nom___du___pole'=> array( 'label' => 'Nom du pôle', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'COMET ID' => array( 'label' => 'Comet ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
		'REFERENTS'=> array( 'label' => 'REFERENTS', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true), 
        'BINOMES'=> array( 'label' => 'BINOMES', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true), 
        'CONTACTS'=> array( 'label' => 'CONTACTS', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true), 
    ),
    'REFERENTS' => array(
        'NOM'=> array( 'label' => 'Nom', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'PRENOM'=> array( 'label' => 'Nom', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'ID___COMET'=> array( 'label' => 'ID Comet', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'EMAIL'=> array( 'label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'MOBILE'=> array( 'label' => 'Mobile', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'delete'=> array( 'label' => 'Delete', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
		'BINOMES'=> array( 'label' => 'BINOMES', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true),
        'POLES'=> array( 'label' => 'POLES', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true),
    ),
);

$moduleFields['appX0PhUGIkBTcWBE'] = array (
	'Aiko Auto Supr' => array(
        'LABEL'=> array( 'label' => 'Label', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'IDCOMET'=> array( 'label' => 'Id COMET', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'SyncSource'=> array( 'label' => 'Sync Source', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false),
        'DEVREC_ID'=> array( 'label' => 'Dev Rec_ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
    ),
);