# Change Log for GetCustomerPrice

## Unreleased

## 1.3

- FIX : Le bug constaté : Une facture créée à partir d'une commande peut ne pas avoir les prix de la commande... - 1.3.5 - *25/01/2022*
  il faut dans le cas de document avec une origine désactiver le comportement par défaut du module car pose des soucis de cohérences.
  Au passage j'ai ajouté une conf permettant de rester sur l'ancien mode de fonctionnement "Incoherent" car on sait jamais.
- FIX : warnings (SQL query success does not guarantee a non-empty resultset) - 1.3.4 - *2021-10-12*
- FIX : v14 compat (no change required); updated module descriptor; new ChangeLog - 1.3.3 - *2021-06-29*
- FIX : various minor fixes
- FIX : min price issue when used with discount

## 1.2
- FIX : error messages (`getNomUrl()` transnoentities)
- NEW : find prices by quantity
- NEW : setup page
- NEW : option to use the min price
- FIX : multicurrency compatibility
- FIX : price and discount not updated when previous prices are fetched
- FIX : various minor fixes

## 1.1
- NEW : date filter: no date limit
- NEW : warning message telling the user where the price comes from
- NEW : can retrieve the discount in addition to the price (option to retrieve price, discount, or both)
- FIX : SQL error while searching in proposal
- FIX : function called only when adding a new line manually
- NEW : define prices by quantity

## 1.0
- NEW : Find customer prices in proposals, orders and invoices
- NEW : Filter by doc type (proposal, order, invoice)
- NEW : Filter by date (prices starting from current year, from previous year)
