<?php
    require_once('./init.php');

    use EM\DBAL\Model\Model;
    use EM\DBAL\Field\Column;
    /**
     * Employees
     */
    class Employees extends Model
    {

        /**
         * fields
         *
         * @return array
         */
        public static function fields():array
        {
            // return [
            //     Column::addId('EmployeeID'),
            //     Column::addVarChar('FirstName'),
            //     Column::addVarChar('LastName'),
            // ];
            $fields = [
                'EmployeeID' => Column::addID(['callback', 'options']),
                'FirstName' => Column::addString(['callback', 'options']),
                'LastName' => Column::addString(['callback', 'options']),
            ];
            return $fields;
        }
    }

    /**
     * Customers
     */
    class Customers extends Model
    {

        /**
         * fields
         *
         * @return array
         */
        public static function fields():array
        {
            // return [
            //     Column::addId('CustomerID'),
            //     Column::addVarChar('ContactName'),
            //     Column::addVarChar('CompanyName'),
            //     Column::addVarChar('ContactTitle'),
            // ];
            $fields = [
                'CustomerID' => Column::addID(['callback', 'options']),
                'ContactName' => Column::addString(['callback', 'options']),
                'CompanyName' => Column::addString(['callback', 'options']),
                'ContactTitle' => Column::addString(['callback', 'options']),
            ];
            return $fields;
        }
    }

    /**
     * Orders
     */
    class Orders extends Model
    {
        /**
         * fields
         *
         * @return array
         */
        public static function fields():array
        {
            // return [
            //     Column::addId('OrderID'),
            //     Column::addVarChar('ShipName'),
            //     Column::addVarChar('ShipAddress'),
            //     Column::addVarChar('ShipCity'),
            //     Column::addVarChar('ShipRegion'),
            //     Column::addVarChar('ShipPostalCode'),
            //     Column::addVarChar('ShipCountry'),
            //     Column::addForeignKey('CustomerID', array(Customers::class, 'objects')),
            //     Column::addForeignKey('EmployeeID', array(Employees::class, 'objects')),
            // ];
            $fields = [
                'OrderID' => Column::addID(['callback', 'options']),
                'ShipName' =>  Column::addString(['callback', 'options']),
                'ShipAddress' => Column::addString(['callback', 'options']),
                'ShipCity' => Column::addString(['callback', 'options']),
                'ShipRegion' => Column::addString(['callback', 'options']),
                'ShipPostalCode' => Column::addInteger(['callback', 'options']),
                'ShipCountry' => Column::addInteger(['callback', 'options']),
                'CustomerID' => Column::addPrimaryKey([array(Customers::class, 'objects'), 'options']),
                'EmployeeID' => Column::addPrimaryKey([array(Employees::class, 'objects'), 'options']),

            ];
            return $fields;
        }
    }

    /**
     * Orders
     */
    // class Orders_Details extends Model
    // {

    //     /**
    //      * fields
    //      *
    //      * @return array
    //      */
    //     public static function fields():array
    //     {
    //         return [
    //             Column::addVarChar('UnitPrice'),
    //             Column::addVarChar('Quantity'),
    //             Column::addVarChar('Discount'),
    //             Column::addForeignKey('OrderID', array(Orders::class, 'objects'))
    //         ];
    //     }
    //     /**
    //      * getTableName
    //      *
    //      * @return string
    //      */
    //     public static function getTableName():string
    //     {
    //         return '`Order Details`';
    //     }
    // }

    // $user_count = Employees::objects()->count();
    // echo 'User count is ' . $user_count . "\n";
    // if ($user_count > 0) {
    //     // $employee = Employees::objects()->findOne(1);
    //     // print_r($employee);
    //     $employees = Employees::objects()->findAll();
    //     // // print_r($employees);
    //     foreach ($employees as $employee) {
    //         echo "Printing Employee data \n";
    //         print_r($employee);
    //         echo "real: ".(memory_get_peak_usage(true)/1024/1024)." MiB\n\n";
    //     }
    // }
    // $customer_count = Customers::objects()->count();
    // echo 'Customer count is ' . $customer_count . "\n";
    // if ($customer_count > 0) {
    //     $customers = Customers::objects()->findAll();
    //     foreach ($customers as $customer) {
    //         echo "Printing Customer data \n";
    //         print_r($customer);
    //         echo "real: ".(memory_get_peak_usage(true)/1024/1024)." MiB\n\n";
    //     }
    // }

    $order_count = Orders::objects()->count();
    echo 'Order count is ' . $order_count . "\n";
    if ($order_count > 0) {
        // $orders = Orders::objects()->findOne(11077);
        $orders = Orders::objects()->findAll();
        // print_r(count($orders));
        // print_r($orders[25]);
        foreach ($orders as $order) {
            echo "Printing order data \n";
            // print_r($order);
            echo "real: ".(memory_get_peak_usage(true)/1024/1024)." MiB\n\n";
        }
    }

    // $order_details = Orders_Details::objects()->findAll();
    // // print_r(count($order_details));
    // $order_details->limit(10, 10);
    // $order_details->orderBy(['UnitPrice DESC']);
    // // print_r($order_details->get());
    // // print_r($order_details[2]);
    // foreach ($order_details as $order_detail) {
    //     echo "Printing order data \n";
    //     print_r($order_detail);
    //     echo "real: ".(memory_get_peak_usage(true)/1024/1024)." MiB\n\n";
    // }