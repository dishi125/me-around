<?php

use Illuminate\Database\Seeder;
use App\Models\Level;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['name' => 'Lv1','points' => 0],
            ['name' => 'Lv2','points' => 100],
            ['name' => 'Lv3','points' => 200],
            ['name' => 'Lv4','points' => 300],
            ['name' => 'Lv5','points' => 400],
            ['name' => 'Lv6','points' => 500],
            ['name' => 'Lv7','points' => 600],
            ['name' => 'Lv8','points' => 700],
            ['name' => 'Lv9','points' => 800],
            ['name' => 'Lv10','points' => 900],
            ['name' => 'Lv11','points' => 1000],
            ['name' => 'Lv12','points' => 1200],
            ['name' => 'Lv13','points' => 1400],
            ['name' => 'Lv14','points' => 1600],
            ['name' => 'Lv15','points' => 1800],
            ['name' => 'Lv16','points' => 2000],
            ['name' => 'Lv17','points' => 2200],
            ['name' => 'Lv18','points' => 2400],
            ['name' => 'Lv19','points' => 2600],
            ['name' => 'Lv20','points' => 2800],
            ['name' => 'Lv21','points' => 3000],
            ['name' => 'Lv22','points' => 3200],
            ['name' => 'Lv23','points' => 3400],
            ['name' => 'Lv24','points' => 3600],
            ['name' => 'Lv25','points' => 3800],
            ['name' => 'Lv26','points' => 4000],
            ['name' => 'Lv27','points' => 4200],
            ['name' => 'Lv28','points' => 4400],
            ['name' => 'Lv29','points' => 4600],
            ['name' => 'Lv30','points' => 4800],
            ['name' => 'Lv31','points' => 5000],
            ['name' => 'Lv32','points' =>5200],
            ['name' => 'Lv33','points' =>5400],
            ['name' => 'Lv34','points' =>5600],
            ['name' => 'Lv35','points' =>5800],
            ['name' => 'Lv36','points' =>6000],
            ['name' => 'Lv37','points' =>6200],
            ['name' => 'Lv38','points' =>6400],
            ['name' => 'Lv39','points' =>6600],
            ['name' => 'Lv40','points' =>6800],
            ['name' => 'Lv41','points' =>7000],
            ['name' => 'Lv42','points' =>7200],
            ['name' => 'Lv43','points' =>7400],
            ['name' => 'Lv44','points' =>7600],
            ['name' => 'Lv45','points' =>7800],
            ['name' => 'Lv46','points' =>8000],
            ['name' => 'Lv47','points' =>8200],
            ['name' => 'Lv48','points' =>8400],
            ['name' => 'Lv49','points' =>8600],
            ['name' => 'Lv50','points' =>8800],
            ['name' => 'Lv51','points' =>9000],
            ['name' => 'Lv52','points' =>9200],
            ['name' => 'Lv53','points' =>9400],
            ['name' => 'Lv54','points' =>9600],
            ['name' => 'Lv55','points' =>9800],
            ['name' => 'Lv56','points' =>10000],
            ['name' => 'Lv57','points' =>10200],
            ['name' => 'Lv58','points' =>10400],
            ['name' => 'Lv59','points' =>10600],
            ['name' => 'Lv60','points' =>10800],
            ['name' => 'Lv61','points' => 11000],
            ['name' => 'Lv62','points' =>11200],
            ['name' => 'Lv63','points' =>11400],
            ['name' => 'Lv64','points' =>11600],
            ['name' => 'Lv65','points' =>11800],
            ['name' => 'Lv66','points' =>12000],
            ['name' => 'Lv67','points' =>12200],
            ['name' => 'Lv68','points' =>12400],
            ['name' => 'Lv69','points' =>12600],
            ['name' => 'Lv70','points' =>12800],
            ['name' => 'Lv71','points' =>13000],
            ['name' => 'Lv72','points' =>13200],
            ['name' => 'Lv73','points' =>13400],
            ['name' => 'Lv74','points' =>13600],
            ['name' => 'Lv75','points' =>13800],
            ['name' => 'Lv76','points' =>14000],
            ['name' => 'Lv77','points' =>14200],
            ['name' => 'Lv78','points' =>14400],
            ['name' => 'Lv79','points' =>14600],
            ['name' => 'Lv80','points' =>14800],
            ['name' => 'Lv81','points' =>15000],
            ['name' => 'Lv82','points' =>15200],
            ['name' => 'Lv83','points' =>15400],
            ['name' => 'Lv84','points' =>15600],
            ['name' => 'Lv85','points' =>15800],
            ['name' => 'Lv86','points' =>16000],
            ['name' => 'Lv87','points' =>16200],
            ['name' => 'Lv88','points' =>16400],
            ['name' => 'Lv89','points' =>16600],
            ['name' => 'Lv90','points' =>16800],
            ['name' => ' Lv91','points' =>17000],
            ['name' => 'Lv92','points' =>17200],
            ['name' => 'Lv93','points' =>17400],
            ['name' => 'Lv94','points' =>17600],
            ['name' => 'Lv95','points' =>17800],
            ['name' => 'Lv96','points' =>18000],
            ['name' => 'Lv97','points' =>18200],
            ['name' => 'Lv98','points' =>18400],
            ['name' => 'Lv99','points' =>18600],
            ['name' => 'Lv100','points' =>18800],
            ['name' => 'Lv101','points' =>19000],
            ['name' => 'Lv102','points' =>19200],
            ['name' => 'Lv103','points' =>19400],
            ['name' => 'Lv104','points' =>19600],
            ['name' => 'Lv105','points' =>19800],
            ['name' => 'Lv106','points' =>20000],
            ['name' => 'Lv107','points' =>20200],
            ['name' => 'Lv108','points' =>20400],
            ['name' => 'Lv109','points' =>20600],
            ['name' => 'Lv110','points' =>20800],
            ['name' => 'Lv111','points' =>21000],
            ['name' => 'Lv112','points' =>21200],
            ['name' => 'Lv113','points' =>  21400],
            ['name' => 'Lv114','points' =>  21600],
            ['name' => 'Lv115','points' =>  21800],
            ['name' => 'Lv116','points' =>  22000],
            ['name' => 'Lv117','points' =>  22200],
            ['name' => 'Lv118','points' =>  22400],
            ['name' => 'Lv119','points' =>  22600],
            ['name' => 'Lv120','points' =>  22800],
            ['name' => 'Lv121','points' =>  23000],
            ['name' => 'Lv122','points' =>  23200],
            ['name' => 'Lv123','points' =>  23400],
            ['name' => 'Lv124','points' =>  23600],
            ['name' => 'Lv125','points' =>  23800],
            ['name' => 'Lv126','points' =>  24000],
            ['name' => 'Lv127','points' =>  24200],
            ['name' => 'Lv128','points' =>  24400],
            ['name' => 'Lv129','points' =>  24600],
            ['name' => 'Lv130','points' =>  24800],
            ['name' => 'Lv131','points' =>  25000],
            ['name' => 'Lv132','points' =>  25200],
            ['name' => 'Lv133','points' =>  25400],
            ['name' => 'Lv134','points' =>  25600],
            ['name' => 'Lv135','points' =>  25800],
            ['name' => 'Lv136','points' =>  26000],
            ['name' => 'Lv137','points' =>  26200],
            ['name' => 'Lv138','points' =>  26400],
            ['name' => 'Lv139','points' =>  26600],
            ['name' => 'Lv140','points' =>  26800],
            ['name' => 'Lv141','points' =>  27000],
            ['name' => 'Lv142','points' =>  27200],
            ['name' => 'Lv143','points' => 27400],
            ['name' => 'Lv144','points' =>  27600],
            ['name' => 'Lv145','points' =>  27800],
            ['name' => 'Lv146','points' =>  28000],
            ['name' => 'Lv147','points' =>  28200],
            ['name' => 'Lv148','points' =>  28400],
            ['name' => 'Lv149','points' =>  28600],
            ['name' => 'Lv150','points' =>  28800],
            ['name' => 'Lv151','points' =>  29000],
            ['name' => 'Lv152','points' =>  29200],
            ['name' => 'Lv153','points' =>  29400],
            ['name' => 'Lv154','points' =>  29600],
            ['name' => 'Lv155','points' =>  29800],
            ['name' => 'Lv156','points' =>  30000],
            ['name' => 'Lv157','points' =>  30200],
            ['name' => 'Lv158','points' =>  30400],
            ['name' => 'Lv159','points' =>  30600],
            ['name' => 'Lv160','points' => 30800],
            ['name' => 'Lv161','points' =>  31000],
            ['name' => 'Lv162','points' =>  31200],
            ['name' => 'Lv163','points' =>  31400],
            ['name' => 'Lv164','points' =>  31600],
            ['name' => 'Lv165','points' =>  31800],
            ['name' => 'Lv166','points' =>  32000],
            ['name' => 'Lv167','points' =>  32200],
            ['name' => 'Lv168','points' =>  32400],
            ['name' => 'Lv169','points' =>32600],
            ['name' => 'Lv170','points' =>32800],
            ['name' => 'Lv171','points' =>33000],
            ['name' => 'Lv172','points' =>33200],
            ['name' => 'Lv173','points' =>33400],
            ['name' => 'Lv174','points' =>33600],
            ['name' => 'Lv175','points' =>33800],
            ['name' => 'Lv176','points' =>34000],
            ['name' => 'Lv177','points' => 34200],
            ['name' => 'Lv178','points' =>  34400],
            ['name' => 'Lv179','points' =>  34600],
            ['name' => 'Lv180','points' =>  34800],
            ['name' => 'Lv181','points' =>  35000],
            ['name' => 'Lv182','points' =>  35200],
            ['name' => 'Lv183','points' =>  35400],
            ['name' => 'Lv184','points' =>  35600],
            ['name' => 'Lv185','points' =>  35800],
            ['name' => 'Lv186','points' =>  36000],
            ['name' => 'Lv187','points' =>  36200],
            ['name' => 'Lv188','points' =>  36400],
            ['name' => 'Lv189','points' =>  36600],
            ['name' => 'Lv190','points' =>  36800],
            ['name' => 'Lv191','points' =>  37000],
            ['name' => 'Lv192','points' =>  37200],
            ['name' => 'Lv193','points' =>  37400],
            ['name' => 'Lv194','points' =>  37600],
            ['name' => 'Lv195','points' =>  37800],
            ['name' => 'Lv196','points' =>  38000],
            ['name' => 'Lv197','points' =>  38200],
            ['name' => 'Lv198','points' =>  38400],
            ['name' => 'Lv199','points' =>  38600],
            ['name' => 'Lv200','points' =>  38800],
            ['name' => 'Lv201','points' =>  39000],
            ['name' => 'Lv202','points' => 39200],
            ['name' => 'Lv203','points' =>   39400],
            ['name' => 'Lv204','points' =>   39600],
            ['name' => 'Lv205','points' =>   39800],
            ['name' => 'Lv206','points' =>   40000],
            ['name' => 'Lv207','points' =>   40200],
            ['name' => 'Lv208','points' =>   40400],
            ['name' => 'Lv209','points' =>   40600],
            ['name' => 'Lv210','points' =>   40800],
            ['name' => 'Lv211','points' =>   41000],
            ['name' => 'Lv212','points' =>   41200],
            ['name' => 'Lv213','points' =>   41400],
            ['name' => 'Lv214','points' =>   41600],
            ['name' => 'Lv215','points' =>   41800],
            ['name' => 'Lv216','points' =>   42000],
            ['name' => 'Lv217','points' =>   42200],
            ['name' => 'Lv218','points' =>   42400],
            ['name' => 'Lv219','points' =>   42600],
            ['name' => 'Lv220','points' =>   42800],
            ['name' => 'Lv221','points' =>   43000],
            ['name' => 'Lv222','points' =>   43200],
            ['name' => 'Lv223','points' =>   43400],
            ['name' => 'Lv224','points' =>   43600],
            ['name' => 'Lv225','points' =>   43800],
            ['name' => 'Lv226','points' =>   44000],
            ['name' => 'Lv227','points' =>   44200],
            ['name' => 'Lv228','points' =>   44400],
            ['name' => 'Lv229','points' =>   44600],
            ['name' => 'Lv230','points' =>   44800],
            ['name' => 'Lv231','points' =>   45000],
            ['name' => 'Lv232','points' =>   45200],
            ['name' => 'Lv233','points' =>   45400],
            ['name' => 'Lv234','points' =>  45600],
            ['name' => 'Lv235','points' =>  45800],
            ['name' => 'Lv236','points' =>  46000],
            ['name' => 'Lv237','points' =>  46200],
            ['name' => 'Lv238','points' =>  46400],
            ['name' => 'Lv239','points' =>  46600],
            ['name' => 'Lv240','points' =>  46800],
            ['name' => 'Lv241','points' =>  47000],
            ['name' => 'Lv242','points' =>  47200],
            ['name' => 'Lv243','points' =>  47400],
            ['name' => 'Lv244','points' =>  47600],
            ['name' => 'Lv245','points' =>  47800],
            ['name' => 'Lv246','points' =>  48000],
            ['name' => 'Lv247','points' =>  48200],
            ['name' => 'Lv248','points' =>  48400],
            ['name' => 'Lv249','points' =>  48600],
            ['name' => 'Lv250','points' =>  48800],
            ['name' => 'Lv251','points' =>  49100],
            ['name' => 'Lv252','points' =>  49400],
            ['name' => 'Lv253','points' =>  49700],
            ['name' => 'Lv254','points' =>  50000],
            ['name' => 'Lv255','points' =>  50300],
            ['name' => 'Lv256','points' =>  50600],
            ['name' => 'Lv257','points' =>  50900],
            ['name' => 'Lv258','points' =>  51200],
            ['name' => 'Lv259','points' =>  51500],
            ['name' => 'Lv260','points' =>  51800],
            ['name' => 'Lv261','points' =>  52100],
            ['name' => 'Lv262','points' =>  52400],
            ['name' => 'Lv263','points' =>  52700],
            ['name' => 'Lv264','points' =>  53000],
            ['name' => 'Lv265','points' =>  53300],
            ['name' => 'Lv266','points' =>  53600],
            ['name' => 'Lv267','points' =>  53900],
            ['name' => 'Lv268','points' =>  54200],
            ['name' => 'Lv269','points' =>  54500],
            ['name' => 'Lv270','points' =>  54800],
            ['name' => 'Lv271','points' =>  55100],
            ['name' => 'Lv272','points' =>  55400],
            ['name' => 'Lv273 ','points'=> 55700],
            ['name' => 'Lv274','points' =>  56000],
            ['name' => 'Lv275','points' =>   56300],
            ['name' => 'Lv276','points' =>   56600],
            ['name' => 'Lv277','points' =>   56900],
            ['name' => 'Lv278','points' =>   57200],
            ['name' => 'Lv279','points' =>   57500],
            ['name' => 'Lv280','points' =>   57800],
            ['name' => 'Lv281','points' =>   58100],
            ['name' => 'Lv282','points' =>   58400],
            ['name' => 'Lv283','points' =>   58700],
            ['name' => 'Lv284','points' =>   59000],
            ['name' => 'Lv285','points' =>   59300],
            ['name' => 'Lv286','points' =>   59600],
            ['name' => 'Lv287','points' =>   59900],
            ['name' => 'Lv288','points' =>   60200],
            ['name' => 'Lv289','points' =>   60500],
            ['name' => 'Lv290','points' =>   60800],
            ['name' => 'Lv291','points' =>  61100],
            ['name' => 'Lv292','points' =>  61400],
            ['name' => 'Lv293','points' =>  61700],
            ['name' => 'Lv294','points' =>  62000],
            ['name' => 'Lv295','points' =>  62300],
            ['name' => 'Lv296','points' =>  62600],
            ['name' => 'Lv297','points' =>  62900],
            ['name' => 'Lv298','points' =>  63200],
            ['name' => 'Lv299','points' =>  63500],
            ['name' => 'Lv300','points' =>  63800],
            ['name' => 'Lv301','points' =>  64100],
            ['name' => 'Lv302','points' =>  64400],
            ['name' => 'Lv303','points' =>  64700],
            ['name' => 'Lv304','points' =>  65000],
            ['name' => 'Lv305','points' =>  65300],
            ['name' => 'Lv306','points' =>  65600],
            ['name' => 'Lv307','points' =>  65900],
            ['name' => 'Lv308','points' =>  66200],
            ['name' => 'Lv309','points' =>  66500],
            ['name' => 'Lv310','points' =>  66800],
            ['name' => 'Lv311','points' =>  67100],
            ['name' => 'Lv312','points' =>  67400],
            ['name' => 'Lv313','points' =>  67700],
            ['name' => 'Lv314','points' =>  68000],
            ['name' => 'Lv315','points' =>  68300],
            ['name' => 'Lv316','points' =>  68600],
            ['name' => 'Lv317','points' =>  68900],
            ['name' => 'Lv318','points' =>  69200],
            ['name' => 'Lv319','points' =>  69500],
            ['name' => 'Lv320','points' =>  69800],
            ['name' => 'Lv321','points' =>  70100],
            ['name' => 'Lv322','points' =>  70400],
            ['name' => 'Lv323','points' =>  70700],
            ['name' => 'Lv324','points' =>  71000],
            ['name' => 'Lv325','points' =>  71300],
            ['name' => 'Lv326','points' =>  71600],
            ['name' => 'Lv327','points' =>  71900],
            ['name' => 'Lv328','points' =>  72200],
            ['name' => 'Lv329','points' =>  72500],
            ['name' => 'Lv330','points' =>  72800],
            ['name' => 'Lv331','points' =>  73100],
            ['name' => 'Lv332','points' =>  73400],
            ['name' => 'Lv333','points' =>  73700],
            ['name' => 'Lv334','points' =>  74000],
            ['name' => 'Lv335','points' =>  74300],
            ['name' => 'Lv336','points' =>  74600],
            ['name' => 'Lv337','points' =>  74900],
            ['name' => 'Lv338','points' =>  75200],
            ['name' => 'Lv339','points' =>  75500],
            ['name' => 'Lv340','points' =>  75800],
            ['name' => 'Lv341','points' =>  76100],
            ['name' => 'Lv342','points' =>  76400],
            ['name' => 'Lv343','points' =>  76700],
            ['name' => 'Lv344','points' =>  77000],
            ['name' => 'Lv345','points' =>  77300],
            ['name' => 'Lv346','points' =>  77600],
            ['name' => 'Lv347','points' =>  77900],
            ['name' => 'Lv348','points' =>  78200],
            ['name' => 'Lv349','points' =>  78500],
            ['name' => 'Lv350','points' =>  78800],
            ['name' => 'Lv351','points' =>  79200],
            ['name' => 'Lv352','points' =>  79600],
            ['name' => 'Lv353','points' =>  80000],
            ['name' => 'Lv354','points' =>  80400],
            ['name' => 'Lv355','points' =>  80800],
            ['name' => 'Lv356','points' =>  81200],
            ['name' => 'Lv357','points' =>  81600],
            ['name' => 'Lv358','points' =>  82000],
            ['name' => 'Lv359','points' =>  82400],
            ['name' => 'Lv360','points' =>  82800],
            ['name' => 'Lv361','points' =>  83200],
            ['name' => 'Lv362','points' =>  83600],
            ['name' => 'Lv363','points' =>  84000],
            ['name' => 'Lv364','points' =>  84400],
            ['name' => 'Lv365','points' =>  84800],
            ['name' => 'Lv366','points' =>  85200],
            ['name' => 'Lv367','points' =>  85600],
            ['name' => 'Lv368','points' =>  86000],
            ['name' => 'Lv369','points' =>  86400],
            ['name' => 'Lv370','points' =>  86800],
            ['name' => 'Lv371','points' =>  87200],
            ['name' => 'Lv372','points' =>  87600],
            ['name' => 'Lv373','points' =>  88000],
            ['name' => 'Lv374','points' =>  88400],
            ['name' => 'Lv375','points' =>  88800],
            ['name' => 'Lv376','points' =>  89200],
            ['name' => 'Lv377','points' =>  89600],
            ['name' => 'Lv378','points' =>  90000],
            ['name' => 'Lv379','points' =>  90400],
            ['name' => 'Lv380','points' =>  90800],
            ['name' => 'Lv381','points' =>  91200],
            ['name' => 'Lv382','points' =>  91600],
            ['name' => 'Lv383','points' =>  92000],
            ['name' => 'Lv384','points' =>  92400],
            ['name' => 'Lv385','points' =>  92800],
            ['name' => 'Lv386','points' =>  93200],
            ['name' => 'Lv387','points' =>  93600],
            ['name' => 'Lv388','points' =>  94000],
            ['name' => 'Lv389','points' =>  94400],
            ['name' => 'Lv390','points' =>  94800],
            ['name' => 'Lv391','points' =>  95200],
            ['name' => 'Lv392','points' =>  95600],
            ['name' => 'Lv393','points' =>  96000],
            ['name' => 'Lv394','points' =>  96400],
            ['name' => 'Lv395','points' =>  96800],
            ['name' => 'Lv396','points' =>  97200],
            ['name' => 'Lv397','points' =>  97600],
            ['name' => 'Lv398','points' =>  98000],
            ['name' => 'Lv399','points' =>  98400],
            ['name' => 'Lv400','points' => 98800],
        ];

        foreach ($data as $item) {

            $level = Level::firstOrCreate([
                'name' => $item['name'],
                'points' => $item['points']
            ]);
            
        }
    }
}
