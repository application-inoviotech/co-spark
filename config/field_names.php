<?php

/*
|--------------------------------------------------------------------------
| Readable field names for validation messages
|--------------------------------------------------------------------------
| The public forms post short field codes ("dlst", "ctype"). These names are used
| in every validation message instead of the code; a form request's own
| attributes() still win. Generic keys (name, type, date…) are left out so
| admin forms keep their natural wording. Array fields are formatted by
| App\Support\FieldNames ("vehicles.1.vin" → "vehicle 2 VIN").
*/

return [
    // People & contact
    'first' => 'first name', 'last' => 'last name', 'aname' => 'applicant name', 'adob' => 'date of birth', 'dob' => 'date of birth',
    'pref' => 'preferred contact method', 'street' => 'street address', 'street2' => 'address line 2', 'st' => 'state', 'stt' => 'state',
    'zip' => 'ZIP code', 'pw' => 'password', 'sms' => 'SMS consent', 'tos' => 'terms', 'relation' => 'relationship to the insured',

    // Appointments
    'a_name' => 'name', 'a_email' => 'email', 'a_phone' => 'phone', 'a_date' => 'preferred date', 'a_time' => 'preferred time',
    'a_reason' => 'reason', 'a_msg' => 'message',

    // Quotes — drivers & vehicles
    'drivers' => 'drivers', 'vehicles' => 'vehicles', 'dl' => 'driver license number', 'dlst' => 'license issuing state',
    'acc' => 'accidents', 'viol' => 'moving violations', 'claims' => 'claims', 'vin' => 'VIN', 'own' => 'ownership',
    'use' => 'primary use', 'miles' => 'annual mileage',

    // Quotes — property
    'paddr' => 'property address', 'raddr' => 'rental address', 'caddr' => 'condo address', 'unit' => 'unit / floor',
    'occ' => 'occupancy', 'occupants' => 'number of occupants', 'length' => 'length of residency', 'dwell' => 'dwelling type',
    'built' => 'year built', 'sqft' => 'square footage', 'beds' => 'bedrooms', 'baths' => 'bathrooms', 'constr' => 'construction type',
    'roof' => 'roof type', 'roofage' => 'roof age', 'ppv' => 'personal property value', 'addv' => 'additions & alterations value',
    'liab' => 'liability limit',

    // Quotes — prior coverage & life
    'insured' => 'insured name', 'carrier' => 'current carrier', 'expires' => 'policy expiration date', 'limits' => 'current coverage limits',
    'decs' => 'declarations page', 'cov' => 'coverage', 'ptype' => 'policy type', 'dur' => 'desired duration',
    'existing' => 'existing life insurance', 'existingAmt' => 'existing coverage amount', 'hazard' => 'hazardous duties',
    'health' => 'health conditions', 'tobacco' => 'tobacco use',

    // Quotes — business
    'legal' => 'legal business name', 'entity' => 'entity type', 'industry' => 'industry', 'baddr' => 'business address',
    'years' => 'years in business', 'revenue' => 'annual revenue', 'emp' => 'employee count', 'locations' => 'number of locations',
    'activities' => 'business activities', 'equip' => 'equipment value', 'property' => 'own or lease',

    // Claims
    'itype' => 'insurance type', 'ctype' => 'claim type', 'loc' => 'incident location', 'desc' => 'description',
    'policy' => 'policy number', 'drivable' => 'vehicle drivable', 'other' => 'other vehicle involved',
    'otherInfo' => 'other driver details', 'injured' => 'injuries', 'injury' => 'injuries', 'police' => 'police report',
    'report' => 'police report number', 'habitable' => 'home habitable', 'mitig' => 'temporary repairs', 'rooms' => 'areas affected',
    'closed' => 'business closed', 'docs' => 'documents', 'photos' => 'photos', 'vehicle' => 'vehicle involved',

    // Producer application
    'exp' => 'insurance experience', 'prev' => 'previous insurance company', 'lines' => 'lines of business', 'lic' => 'licensed status',
    'licno' => 'license number', 'lictype' => 'license type', 'licst' => 'license state', 'issue' => 'license issue date',
    'exp_d' => 'license expiration date', 'why' => 'reason for applying', 'appfile' => 'completed producer application',
    'licfile' => 'insurance license',

    // Member & producer dashboards
    'freq' => 'billing frequency', 'payment_method' => 'payment method', 'method_id' => 'payment method', 'auto_renew' => 'auto-renew',
    'check_in' => 'check-in date', 'check_out' => 'check-out date', 'guests' => 'number of guests', 'token_type' => 'token type',
    'follow_up_on' => 'follow-up date', 'indemnity' => 'hold-harmless acknowledgment',
];
