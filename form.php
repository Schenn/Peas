
<?php

     if(isset($_GET['action'])){
          print_r($_GET);
     }

?>

<html>
     <body>
          <!-- name, species, planet, system, solar_years, class -->
          <div>
          </div>
          <form action="formTest.php" method="post" >
               <table>
                    <th>Insert</th>
                    <tr>
                         <td><label for="name">Name:</label></td><td><input type="text" name="name" /></td>
                    </tr>
                    <tr>
                         <td><label for="species">Species:</label></td><td><input type="text" name="species" /></td>
                    </tr>
                    <tr>
                         <td><label for="planet">Planet:</label></td><td><input type="text" name="planet" /></td>
                    </tr>
                    <tr>
                         <td><label for="system">System:</label></td><td><input type="text" name="system" /></td>
                    </tr>
                    <tr>
                         <td><label for="solar_years">Solar_Years:</label></td><td><input type="text" name="solar_years" /></td>
                    </tr>
                    <tr>
                         <td><label for="class">Class:</label></td><td><input type="text" name="class" /></td>
                    </tr>
                    <tr>
                         <td><input type="hidden" name="action" value="insert" /></td>
                    </tr>
                    </tr>
                         <td><input type="submit" /></td><td><input type="reset" /></td>
                    <tr>
               </table>
          </form>
          <form action="formTest.php" method="get">
               <table>
                    <th>Select</th>
                    <tr>
                         <td><label for='cols'>Columns:</label></td>
                         <td><input type='text' placeholder='"," to seperate' name='cols' /> </td>
                    </tr>
                    <tr>
                         <td><input type='checkbox' name='aggSolar'/>Aggregate?</td>
                         <td><label for='agg'>Aggregate Solar Years</label></td>
                         <td><select size = '3' name='aggregateMethod'>
                              <option value='sum' selected='selected'>Sum</option>
                              <option value='avg'>Average</option>
                              <option value='max'>Max</option>
                              <option value='min'>Min</option>
                              <option value='count'>Count</option>
                         </select></td>
                    </tr>
                    <tr><td>From pdoi_tester</td></tr>
                    <tr><td>Where</td></tr>
                    <tr>
                         <td><label for='whereName'>Name:</label></td>
                         <td><select size = '5' name='whereNameMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereName' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereSpecies'>Species:</label></td>
                         <td><select size = '5' name='whereSpeciesMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereSpecies' /></td>
                    </tr>
                    <tr>
                         <td><label for='wherePlanet'>Planet:</label></td>
                         <td><select size = '5' name='wherePlanetMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='wherePlanet' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereSystem'>System:</label></td>
                         <td><select size = '5' name='whereSystemMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereSystem' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereSolar'>Solar Years:</label></td>
                         <td><select size = '5' name='whereSolarMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='<'><</option>
                              <option value='less'>Less Than</option>
                              <option value='<='><=</option>
                              <option value='less equal'>Less Than or Equal To</option>
                              <option value='>'>></option>
                              <option value='greater'>Greater Than</option>
                              <option value='>='>>=</option>
                              <option value='greater equal'>Greater Than or Equal To</option>
                         </select></td>
                         <td><input type='text' name='whereSolar' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereClass'>Class:</label></td>
                         <td><select size = '5' name='whereClassMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereClass' /></td>
                    </tr>
                    <tr>
                         <td><label for='orderby'>Order By:</label></td><td><input type= "text" name="orderby"/></td>
                         <td><input type='radio' name='orderMethod' value='ASC' />Ascending<br />
                              <input type='radio' name='orderMethod' value='DESC' />Descending<br />
                              <input type='radio' name='orderMethod' value='none' checked='checked' />None
                         </td>
                    </tr>
                    <tr>
                         <td><label for='groupby'>Group By:</label></td><td><input type= "text" name="groupby"/></td>
                         <td><input type='checkbox' name='havingSolar'/>Having?</td>
                         <td><label for='havingMethod'>Having: Aggregate Solar Years:</label></td>
                         <td><select size = '3' name='havingMethod'>
                              <option value='sum'>Sum</option>
                              <option value='avg'>Average</option>
                              <option value='max'>Max</option>
                              <option value='min'>Min</option>
                              <option value='count'>Count</option>
                         </select></td>
                         <td><select size = '5' name='havingSolarMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='<'><</option>
                              <option value='less'>Less Than</option>
                              <option value='<='><=</option>
                              <option value='less equal'>Less Than or Equal To</option>
                              <option value='>'>></option>
                              <option value='greater'>Greater Than</option>
                              <option value='>='>>=</option>
                              <option value='greater equal'>Greater Than or Equal To</option>
                         </select></td>
                         <td><input type='text' name='havingSolarValue' /></td>
                    </tr>
                    <tr>
                         <td><label for='limit'>Limit:</label></td>
                         <td><input type='text' name='limit' /> </td>
                    </tr>
                    <tr>
                         <td><input type="hidden" name="action" value="select1" /></td>
                    </tr>
                    <tr>
                         <td><input type="submit" /></td><td><input type="reset" /></td>
                    </tr>
               </table>
          </form>
          <form action="formTest.php" method="post">
               <table>
                    <th>Update pdoi_tester SET </th>
                    <tr>
                         <td><label for="name">Name:</label></td><td><input type="text" name="name" /></td>
                    </tr>
                    <tr>
                         <td><label for="species">Species:</label></td><td><input type="text" name="species" /></td>
                    </tr>
                    <tr>
                         <td><label for="planet">Planet:</label></td><td><input type="text" name="planet" /></td>
                    </tr>
                    <tr>
                         <td><label for="system">System:</label></td><td><input type="text" name="system" /></td>
                    </tr>
                    <tr>
                         <td><label for="solar_years">Solar_Years:</label></td><td><input type="text" name="solar_years" /></td>
                    </tr>
                    <tr>
                         <td><label for="class">Class:</label></td><td><input type="text" name="class" /></td>
                    </tr>
                    <tr><td> Where </td></tr>
                    <tr>
                         <td><label for='whereName'>Name:</label></td>
                         <td><select size = '5' name='whereNameMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereName' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereSpecies'>Species:</label></td>
                         <td><select size = '5' name='whereSpeciesMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereSpecies' /></td>
                    </tr>
                    <tr>
                         <td><label for='wherePlanet'>Planet:</label></td>
                         <td><select size = '5' name='wherePlanetMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='wherePlanet' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereSystem'>System:</label></td>
                         <td><select size = '5' name='whereSystemMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereSystem' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereSolar'>Solar Years:</label></td>
                         <td><select size = '5' name='whereSolarMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='<'><</option>
                              <option value='less'>Less Than</option>
                              <option value='<='><=</option>
                              <option value='less equal'>Less Than or Equal To</option>
                              <option value='>'>></option>
                              <option value='greater'>Greater Than</option>
                              <option value='>='>>=</option>
                              <option value='greater equal'>Greater Than or Equal To</option>
                         </select></td>
                         <td><input type='text' name='whereSolar' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereClass'>Class:</label></td>
                         <td><select size = '5' name='whereClassMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereClass' /></td>
                    </tr>
                    <tr>
                         <td><label for='orderby'>Order By:</label></td><td><input type= "text" name="orderby"/></td>
                         <td><input type='radio' name='orderMethod' value='ASC' />Ascending<br />
                              <input type='radio' name='orderMethod' value='DESC' />Descending<br />
                              <input type='radio' name='orderMethod' value='none' checked='checked' />None
                         </td>
                    </tr>
                    <tr>
                         <td><label for='limit'>Limit:</label></td>
                         <td><input type='text' name='limit' /> </td>
                    </tr>
                    <tr>
                         <td><input type="hidden" name="action" value="update" /></td>
                    </tr>
                    <tr>
                         <td><input type="submit" /></td><td><input type="reset" /></td>
                    </tr>
               </table>
          </form>
          <form action='formTest.php' method='post'>
               <table>
                    <th>Delete</th>
                    <tr><td>Where</td></tr>
                    <tr>
                         <td><label for='whereName'>Name:</label></td>
                         <td><select size = '5' name='whereNameMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereName' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereSpecies'>Species:</label></td>
                         <td><select size = '5' name='whereSpeciesMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereSpecies' /></td>
                    </tr>
                    <tr>
                         <td><label for='wherePlanet'>Planet:</label></td>
                         <td><select size = '5' name='wherePlanetMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='wherePlanet' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereSystem'>System:</label></td>
                         <td><select size = '5' name='whereSystemMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereSystem' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereSolar'>Solar Years:</label></td>
                         <td><select size = '5' name='whereSolarMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='<'><</option>
                              <option value='less'>Less Than</option>
                              <option value='<='><=</option>
                              <option value='less equal'>Less Than or Equal To</option>
                              <option value='>'>></option>
                              <option value='greater'>Greater Than</option>
                              <option value='>='>>=</option>
                              <option value='greater equal'>Greater Than or Equal To</option>
                         </select></td>
                         <td><input type='text' name='whereSolar' /></td>
                    </tr>
                    <tr>
                         <td><label for='whereClass'>Class:</label></td>
                         <td><select size = '5' name='whereClassMethod'>
                              <option value='=' selected='selected'>=</option>
                              <option value='!='>!=</option>
                              <option value='not'>NOT</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                         </select></td>
                         <td><input type='text' name='whereClass' /></td>
                    </tr>
                    <tr>
                         <td><label for='orderby'>Order By:</label></td><td><input type= "text" name="orderby"/></td>
                         <td><input type='radio' name='orderMethod' value='ASC' />Ascending<br />
                              <input type='radio' name='orderMethod' value='DESC' />Descending<br />
                              <input type='radio' name='orderMethod' value='none' checked='checked' />None
                         </td>
                    </tr>
                    <tr>
                         <td><label for='limit'>Limit:</label></td>
                         <td><input type='text' name='limit' /> </td>
                    </tr>
                    <tr>
                         <td><input type="hidden" name="action" value="delete" /></td>
                    </tr>
                    <tr>
                         <td><input type="submit" /></td><td><input type="reset" /></td>
                    </tr>
               </table>
          </form>
     </body>
</html>