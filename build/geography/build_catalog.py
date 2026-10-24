from pathlib import Path
import json, zipfile, io, collections, datetime

root=Path(__file__).resolve().parents[2]
src=root/'build/geography'; out=root/'writable/reference/geography'
out.mkdir(parents=True,exist_ok=True)
countries={}
for line in (src/'countryInfo.txt').read_text(encoding='utf-8').splitlines():
    if not line or line.startswith('#'):continue
    p=line.split('\t')
    if len(p)>4:countries[p[0]]={'code':p[0],'name':p[4]}
catalogs={code:{'country':info,'states':[],'counties':{},'cities':{},'lookup':{code:{'code':code,'name':info['name'],'country':code,'state':'','county':'','city':'','level':'country'}}} for code,info in countries.items()}
state_names={};county_names={}
for line in (src/'admin1CodesASCII.txt').read_text(encoding='utf-8').splitlines():
    p=line.split('\t'); key=p[0].split('.')
    if len(key)!=2 or key[0] not in catalogs:continue
    country,state=key; code='-'.join(key); name=p[1];state_names[(country,state)]=name
    entry={'code':code,'name':name,'country':country,'state':code,'county':'','city':'','level':'state'}
    catalogs[country]['states'].append({'code':code,'name':name});catalogs[country]['lookup'][code]=entry
for line in (src/'admin2Codes.txt').read_text(encoding='utf-8').splitlines():
    p=line.split('\t');key=p[0].split('.')
    if len(key)!=3 or key[0] not in catalogs:continue
    country,state,county=key;state_code=f'{country}-{state}';code='-'.join(key)
    county_names[(country,state,county)]=p[1]
    catalogs[country]['counties'].setdefault(state_code,[]).append({'code':code,'name':p[1]})
    catalogs[country]['lookup'][code]={'code':code,'name':p[1],'country':country,'state':state_code,'county':code,'city':'','level':'county'}
count=0
with zipfile.ZipFile(src/'cities500.zip') as z:
    with z.open('cities500.txt') as stream:
        for line in io.TextIOWrapper(stream,encoding='utf-8'):
            p=line.rstrip('\n').split('\t')
            if len(p)<19 or p[8] not in catalogs:continue
            country,state,county=p[8],p[10],p[11]
            state_code=f'{country}-{state}' if state else ''
            county_code=f'{state_code}-{county}' if state and county and f'{state_code}-{county}' in catalogs[country]['lookup'] else ''
            valid_state=state_code if state_code in catalogs[country]['lookup'] else ''
            code=f'{county_code or valid_state or country}-C{p[0]}'
            entry={'code':code,'name':p[1],'country':country,'state':state_code,'county':county_code,'city':code,'level':'city'}
            # Unknown administrative divisions stay unassigned rather than inventing a county.
            if state_code and state_code not in catalogs[country]['lookup']:entry['state']='';entry['county']=''
            catalogs[country]['lookup'][code]=entry
            catalogs[country]['cities'].setdefault(entry['state'],[]).append({'code':code,'name':p[1],'county':entry['county']})
            count+=1
for code,data in catalogs.items():
    data['states'].sort(key=lambda x:x['name'].casefold())
    for group in ['counties','cities']:
        for rows in data[group].values():rows.sort(key=lambda x:x['name'].casefold())
    (out/f'{code}.json').write_text(json.dumps(data,ensure_ascii=False,separators=(',',':')),encoding='utf-8')
(out/'countries.json').write_text(json.dumps(sorted(countries.values(),key=lambda x:x['name'].casefold()),ensure_ascii=False),encoding='utf-8')
(out/'source.json').write_text(json.dumps({'source':'GeoNames','url':'https://www.geonames.org/','license':'CC BY 4.0','license_url':'https://creativecommons.org/licenses/by/4.0/','retrieved':'2026-10-23','dataset':'cities500, countryInfo, admin1CodesASCII, admin2Codes','countries':len(countries),'cities':count}),encoding='utf-8')
print(f'Prepared {len(countries)} countries and {count} cities with supplied administrative divisions.')
