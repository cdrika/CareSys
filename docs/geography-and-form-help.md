# Guided forms and location reference data

FormCatalog defines shared application vocabulary for professional roles, skills,
qualifications, work preferences, languages, care settings and travel distance.
The lists are common application suggestions, not an accreditation standard or
a claim of competence. Single-choice fields offer Other; multiple-choice fields
support searchable choices, selected tags, removal and custom text. Qualifications
remain self-declared until separately reviewed.

The shared form component supplies contextual question-mark help with brief
explanations and examples. Native dialogs support keyboard navigation, Escape,
focus return and an explicit close button. The same pattern is used for other
agency forms and login/registration. Editable durations display hours or minutes
and convert to the service's existing stored units.

## Geography

Country, state/province, county/district and city/town choices are populated from
GeoNames. The imported snapshot contains 252 country/territory entries and 236,640
populated places, using supplied administrative codes. These are work preferences,
not residential addresses, geolocation permission or a commute-time calculation.
Users may choose a whole country, state or county and add up to 20 preferred areas.
Internal location references are derived automatically and are not user-entered.

Source: https://www.geonames.org/ and
https://download.geonames.org/export/dump/ .
Files: countryInfo.txt, admin1CodesASCII.txt, admin2Codes.txt and cities500.zip.
Retrieved 23 October 2026. License: Creative Commons Attribution 4.0,
https://creativecommons.org/licenses/by/4.0/ .

The cities500 extract includes populated places with at least approximately 500
people and administrative seats. It is not an exhaustive gazetteer of every small
settlement. Administrative structures differ by country; missing subdivisions are
left unassigned rather than invented. The interface permits choosing a broader
area and explains that some places may be missing.

Generated country catalogs are under writable/reference/geography, outside the
public directory. The authenticated reference endpoint returns only the requested
public place names and hierarchy. It does not send any user data to GeoNames.
The preparation script is build/geography/build_catalog.py; the downloaded source
files remain there for reproducibility. Source metadata is saved as source.json.
The original US-CA-SAC and US-CA-YOLO demo preferences resolve to their corresponding
counties without requiring a user to understand or re-enter legacy codes.

## Verification

PHP tests cover common choices, vocabulary separation, location hierarchy and
legacy preferences. Acceptance tests verify structured profile save and hierarchical
recruitment matching. The form-usability browser test exercises help dialog focus,
standard/custom entries, two selected cities, persistence after reload, mobile
layout and contextual help on the agency forms.
