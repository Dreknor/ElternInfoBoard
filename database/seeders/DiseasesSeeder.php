<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiseasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dieases = [
            ['name' => 'Cholera',
                'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt',
                'wiederzulassung_wann' => 'nach Genesung und 2 negativen Stuhlproben',
                'aushang_dauer' => 5,
            ],
            [
                'name' => 'Diphtherie',
                'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt',
                'wiederzulassung_wann' => 'nach Behandlung und 2 negativen Abstrichen',
                'aushang_dauer' => 10,
            ],
            [
                'name' => 'Enteritis (EHEC)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt',
                'wiederzulassung_wann' => 'nach Genesung und 2 negativen Stuhlproben',
                'aushang_dauer' => 10,
            ],
            [
                'name' => 'Salmonellen, Yersinien, Campylobacter, E-Coli',
                'reporting' => true,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => '48 Stunden nach Abklingen der Symptome',
                'aushang_dauer' => 10,
            ],
            [
                'name' => 'Lamblien (Giardiasis)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => '48 Stunden nach Abklingen der Symptome',
                'aushang_dauer' => 25,
            ],
            [
                'name' => 'Virusenteritis (Adeno-,Rota-, Noro-, Astroviren)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => '48 Stunden nach Abklingen der Symptome',
                'aushang_dauer' => 10,
            ],
            [
                'name' => 'Infektiöse Mononucleose (Epstein-Barr-Virus)',
                'reporting' => false,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => 'nach Genesung',
                'aushang_dauer' => 12,
            ],
            [
                'name' => 'Haemophilus influenzae Typ b-Meningitis oder -Epiglottitis (Kehldeckelentzündung)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt oder Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => 'nach Genesung, ggf. 24 Stunden nach Beginn der Antibiotika-Therapie',
                'aushang_dauer' => 0,
            ],
            [
                'name' => 'Hand-Fuß-Mund-Krankheit (Coxsackie-Virus, Entero-Virus)',
                'reporting' => false,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => 'nach Abheilung der Bläschen (meist 7 bis 10 Tage)',
                'aushang_dauer' => 0,
            ],
            [
                'name' => 'Impetigo contagiosa (Borkenflechte)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Arzt',
                'wiederzulassung_wann' => '24 Stunden nach Antibiotika oder ohne Antibiotika nach Abheilen der betroffenen Hautareale',
                'aushang_dauer' => 10,
            ],
            [
                'name' => 'Keratoconjunktivitis (Bindehautentz./Adenoviren)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Arzt',
                'wiederzulassung_wann' => 'nach Genesung',
                'aushang_dauer' => 12,
            ],
            [
                'name' => 'Keuchhusten (Pertussis)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Geundheitsamt',
                'wiederzulassung_wann' => '5 Tage nach Beginn Antibiotika-Therapie oder 21 Tage nach Erkrankung ohne Behandlung oder negativer Abstrich (PCR)',
                'aushang_dauer' => 20,
            ],
            [
                'name' => 'Kopfläuse',
                'reporting' => true,
                'wiederzulassung_durch' => 'Sorgeberechtigte oder ärztliche Bescheinigung bei wiederholtem Befall',
                'wiederzulassung_wann' => 'nach Behandlung',
                'aushang_dauer' => 7,
            ],
            [
                'name' => 'Masern',
                'reporting' => true,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => '5 Tage nach Beginn des Hautausschlags bzw. nach Genesung',
                'aushang_dauer' => 21,
            ],
            [
                'name' => 'Meningokokken - Meningitis',
                'reporting' => true,
                'wiederzulassung_durch' => 'Sorgeberechtigte',
                'wiederzulassung_wann' => 'nach Genesung und nach Antibiotika-Therapie',
                'aushang_dauer' => 10,
            ],
            [
                'name' => 'Mumps',
                'reporting' => true,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => 'nach Genesung, frühestens 5 Tage nach Parotisschwellung',
                'aushang_dauer' => 25,
            ],
            [
                'name' => 'Pest',
                'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt',
                'wiederzulassung_wann' => 'gesund nach Behandlung',
                'aushang_dauer' => 7,
            ],
            [
                'name' => 'Poliomyelitis',
                'reporting' => true,
                'wiederzulassung_durch' => 'Geundheitsamt',
                'wiederzulassung_wann' => 'nach 2 negativen Stuhlproben im Abstand von 7 Tagen',
                'aushang_dauer' => 35,
            ],
            [
                'name' => 'Ringelröteln (Parvovirus B19) (kritisch 8. bis 39. SSW)',
                'reporting' => false,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => 'nach Genesung (bei Exanthem nicht mehr infektiös), schwangere Kontaktpersonen: Arzt konsultieren!',
                'aushang_dauer' => 14,
            ],
            [
                'name' => 'Röteln',
                'reporting' => true,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => 'nach Genesung, frühestens 8 Tage nach Exanthem',
                'aushang_dauer' => 21,
            ],
            [
                'name' => 'Scharlach oder Angina (Tonsillenpharyngitis) durch Streptokokken Gruppe A',
                'reporting' => true,
                'wiederzulassung_durch' => 'Arzt',
                'wiederzulassung_wann' => 'ab 2. Therapietag und klinisch gesund, sonst nach Abklingen der Symptome',
                'aushang_dauer' => 4,
            ],
            [
                'name' => 'Shigellose (bakterielle Ruhr)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt',
                'wiederzulassung_wann' => 'nach Genesung und 2 negativen Stuhlproben',
                'aushang_dauer' => 4,
            ],
            [
                'name' => 'Skabies (Krätze)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Sorgeberechtigte, oder ärztl. Attest bei wiederholtem Befall',
                'wiederzulassung_wann' => '24 Stunden nach Behandlung',
                'aushang_dauer' => 35,
            ],
            [
                'name' => 'Tbc',
                'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt',
                'wiederzulassung_wann' => '3 Wochen nach Behandlung',
                'aushang_dauer' => 0,
            ],
            [
                'name' => 'Typhus',
                'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt',
                'wiederzulassung_wann' => 'nach Genesung und 3 negativen Stuhlproben',
                'aushang_dauer' => 14,
            ],
            [
                'name' => 'Virushepatitis A',
                'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt',
                'wiederzulassung_wann' => '2 Wochen nach Symptomatik bzw. 1 Woche nach Ikterus',
                'aushang_dauer' => 30,
            ],
            [
                'name' => 'Virushepatitis E',
                'reporting' => true,
                'wiederzulassung_durch' => 'Geundheitsamt',
                'wiederzulassung_wann' => 'nach Genesung',
                'aushang_dauer' => 64,
            ],
            [
                'name' => 'Virushepatitis B, C, oder D',
                'reporting' => false,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => 'nach Genesung',
                'aushang_dauer' => 0,
            ],
            [
                'name' => 'Virusbedingte Hämorrhagische Fieber (VHF) (Ebola-, Lassa-, Marburg-, Krim-Kongo-Fieber)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Gesundheitsamt',
                'wiederzulassung_wann' => 'nach Genesung',
                'aushang_dauer' => 21,
            ],
            [
                'name' => 'Windpocken (Varizellen)',
                'reporting' => true,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => 'nach Eintrocknen der letzten Bläschen',
                'aushang_dauer' => 16,
            ],
            [
                'name' => 'Herpes zoster (Gürtelrose)',
                'reporting' => false,
                'wiederzulassung_durch' => 'Arzt (kein Attest notwendig)',
                'wiederzulassung_wann' => 'nach Eintrocknen der letzten Bläschen oder bei zuverlässiger Abdeckung',
                'aushang_dauer' => 0,
            ]


        ];

        \App\Model\Disease::insert($dieases);

        \App\Model\Module::insert([
            'setting' => 'meldepfl. Erkrankungen',
            'description' => 'Ermöglicht die Erfassung meldepflichtiger Erkrankungen direkt bei der Krankmeldung. Diese werden dann Im Nachrichtenbereich angezeigt.',
            'category' => 'module',
            'options' => '{
            "active":"1",
            "rights":[],
            "home-view-top":"krankmeldung.diseases",
            "adm-nav":
                {"adm-rights":["manage diseases"],"name":"neue Erkrankung","link":"diseases\/create","icon":"fas fa-pills"}
            }',
        ]);
    }
}
