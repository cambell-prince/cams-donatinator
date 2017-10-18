export class AppModel {

    public payAmount: string;
    
    public payVia: string;

    constructor(
        public currency: string,
        public frequency: string,
        public paySelection: string,
    ) {
        this.payAmount = '';
        this.payVia = '';
    }

}
