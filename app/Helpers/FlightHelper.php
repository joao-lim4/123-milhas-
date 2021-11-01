<?php

namespace App\Helpers;



class FlightHelper {

    /**
     * @var outbounds
     */
    private $outbounds = [
        "size"      => 0,
        "data"      => [],
        "groupFare" => []
    ];

     /**
     * @var flights
     */
    private $flights = [];

     /**
     * @var inbounds
     */
    private $inbounds = [
        "size"      => 0,
        "data"      => [],
        "groupFare" => []
    ];

    /**
     * @var groups
    */
    private $groups = [];

    /**
     * @var infos
    */
    private $infos = [
        "totalGroups"   => 0,
        "totalFlights"  => 0,
        "cheapestPrice" => 999999,
        "cheapestGroup" => 0
    ];

    public function __construct(array $flights)
    {
        $this->flights = $flights;
        $this->initGroups($flights, (sizeof($flights) - 1));
        $this->separateGroupFlight($this->outbounds["groupFare"], $this->inbounds["groupFare"]);
        $this->changeInfos();

        $this->organizePrices();
    }

    /**
     * initGroups separa todos os voos em um array contendo somente
     * voos de ida e outro array contendo voos de volta.
     *
     * @var inbounds voos de volta
     * @var outbounds voos de ida
     */
    private function initGroups (array $flights, int $initPostion)
    {
        if($flights[$initPostion]["outbound"] === 1) {
            $this->outbounds["size"] += 1;
            $this->outbounds["data"][] = $flights[$initPostion];
            $this->initFareGroup($flights[$initPostion]["fare"], $flights[$initPostion], "outbounds");
        }else{
            $this->inbounds["size"] += 1;
            $this->inbounds["data"][] = $flights[$initPostion];
            $this->initFareGroup($flights[$initPostion]["fare"], $flights[$initPostion], "inbounds");
        }

        if(($initPostion - 1) < 0) return false;

        return $this->initGroups($flights, ($initPostion - 1));
    }



    private function initFareGroup(string $fareKey, array $flight, string $objectThis): void
    {
        if(array_key_exists($fareKey, $this->{$objectThis}["groupFare"])) {
            $this->{$objectThis}["groupFare"][$fareKey]["size"] += 1;
        }else{
            $this->{$objectThis}["groupFare"][$fareKey]["size"] = 1;
        }

        $this->{$objectThis}["groupFare"][$fareKey]["data"][] = $flight;
    }


    /**
     * Gera os primeiros grupos de voos, contendo
     * somente voos de ida.
     */
    private function generateInitialOutboundGroup(array $DataGroupFareOut, array $data, int $sizeOut): array
    {

        $data[] = array(
            "uniqueId"   => md5(uniqid("")),
            "totalPrice" => $DataGroupFareOut[$sizeOut]["price"],
            "outbound"   => [
                $DataGroupFareOut[$sizeOut],
            ],
            "inbound"    => [],
        );

        if(($sizeOut - 1) < 0) return $data;

        return $this->generateInitialOutboundGroup($DataGroupFareOut, $data, ($sizeOut - 1));
    }

    /**
     * Caso a quantidade de grupos que tem somente um voo de ida
     * seja maior que a quantidade de voos de volta distribuirei todos
     * os voos de volta randomicamente entre os grupos somente com voos de ida
     * e os grupos que ficarem sem o voo de volta, eu agruparei juntamente a outros grupos
     * ja completos.
     *
     * Podendo assim um grupo ter mais de uma opcao de ida.
     */
    private function spreadRoundTripFlights(array $DataGroupFare, array $flights, int $flightsSize, int $sizeDataGroupFare): array
    {
        $arraySort = range(0, ($sizeDataGroupFare - 1));

        for($i = 0; $i < $flightsSize; $i++) {
            $sortPosition = array_rand($arraySort);
            $DataGroupFare[$arraySort[$sortPosition]]["inbound"][] = $flights[$i];
            $DataGroupFare[$arraySort[$sortPosition]]["totalPrice"] += $flights[$i]["price"];

            if(count($arraySort) > 1) {
                unset($arraySort[$sortPosition]);
            }
        }

        if($sizeDataGroupFare > 1) {

            $arrNoInbound = [];
            for($i = 0; $i < $sizeDataGroupFare; $i++) {
                if(!count($DataGroupFare[$i]["inbound"])) {
                    $arrNoInbound[] = $DataGroupFare[$i];
                    unset($DataGroupFare[$i]);
                }
            }

            sort($DataGroupFare);

            $newArraySort = range(0, (sizeof($DataGroupFare) - 1));

            for($i = 0; $i < sizeof($arrNoInbound); $i++) {
                $newSortPosition = array_rand($newArraySort);
                $DataGroupFare[$newArraySort[$newSortPosition]]["outbound"][] = $arrNoInbound[$i]["outbound"][0];
                $DataGroupFare[$newArraySort[$newSortPosition]]["totalPrice"] += $arrNoInbound[$i]["totalPrice"];
            }
        }

        return $DataGroupFare;
    }


    /**
     * Apos gerar os grupos de voos somente com ida,
     * distribuirei todas os voos de volta
     */
    private function spreadReturnFlights(array $DataGroupFare, array $flights, int $flightsSize): array
    {
        $sizeDataGroupFare = sizeof($DataGroupFare);

        if($sizeDataGroupFare > 1) {
            /**
             *  Caso a quantidade de grupos somente com voos de ida seja maior
             *  que a quantidade de voos de volta eu acabo espalhando os voos
             *  de volta entre os grupos que tem somente idas e se ficou gropos
             *  somente com voos de ida esses voos de ida sao espalhados entre os
             *  grupos existentes
            */
            if($sizeDataGroupFare > $flightsSize) {
                return $this->spreadRoundTripFlights($DataGroupFare, $flights, $flightsSize, $sizeDataGroupFare);
            }
        }else {
            return $this->spreadRoundTripFlights($DataGroupFare, $flights, $flightsSize, $sizeDataGroupFare);
        }

        return $DataGroupFare;
    }


    /**
     * Ira separar e gerar todos os grupos de voos
     */
    private function separateGroupFlight(array $GroupFareOut, array $GroupFareIn): void
    {
        $groups = [];

        //gupos somente com viagens de ida
        foreach($GroupFareOut as $FareOutKey => $FareOut) {
            $groups[$FareOutKey] = $this->generateInitialOutboundGroup($FareOut["data"], [], ($FareOut["size"] - 1));
            $groups[$FareOutKey] = $this->spreadReturnFlights($groups[$FareOutKey], $GroupFareIn[$FareOutKey]["data"], $GroupFareIn[$FareOutKey]["size"]);
        }

        $this->groups = $groups;
    }

    /**
     * Ao finalizar de gerar todos os grupos
     * e gerada novas informacoes gerais
     * sobre os grupos
     */
    private function changeInfos(): void
    {
        foreach($this->groups as $groups) {
            $this->infos["totalGroups"] += sizeof($groups);

            foreach($groups as $group) {
                if($group["totalPrice"] < $this->infos["cheapestPrice"]) {
                    $this->infos["cheapestPrice"] = $group["totalPrice"];
                    $this->infos["cheapestGroup"] = $group["uniqueId"];
                }

                if(!sizeof($group["outbound"]) || !sizeof($group["inbound"])) {
                    $this->infos["totalFlights"] += 1;
                }
            }
        }
    }

    /**
     * Vai orgnaizar os grupos gerados
     * em ordem crescente usando como chave
     * o campo totalPrice
     */
    private function organizePrices(): void
    {
        $newGroups = [];

        foreach($this->groups as $groups) {
            foreach($groups as $group) {
                $newGroups[(int) $group["totalPrice"]] = $group;
            }
        }
        sort($newGroups);

        $this->groups = $newGroups;
    }


    public function getData(): array
    {
        return array(
            "flights"       => $this->flights,
            "groups"        => $this->groups,
            "totalGroups"   => $this->infos["totalGroups"],
            "totalFlights"  => $this->infos["totalFlights"],
            "cheapestPrice" => $this->infos["cheapestPrice"],
            "cheapestGroup" => $this->infos["cheapestGroup"]
        );
    }






}
