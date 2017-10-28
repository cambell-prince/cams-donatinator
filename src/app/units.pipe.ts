import { Pipe, PipeTransform } from '@angular/core';

@Pipe({ name: 'units' })
export class UnitsPipe implements PipeTransform {

    static standardPowers: string[] = [ '', 'K', 'M', 'G', 'T' ];
    
    transform(n: number, startAtPower: number) {
        startAtPower = (typeof startAtPower === 'undefined') ? 0 : startAtPower;
        var power = Math.log10(n);
        var stdPower: number = Math.floor(power / 3);
        if (!(power > startAtPower && stdPower >= 0 && stdPower < UnitsPipe.standardPowers.length)) {
            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        var divisor: number = Math.pow(10, stdPower * 3);
        n = n / divisor;
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + UnitsPipe.standardPowers[stdPower];
    }

}