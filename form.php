
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
                    <!-- While the select function allows for custom column selection for simplicity the test retrieves all -->
                    <th>Select * [Where column method value]</th>
                    <tr>
                         <td><label for='column'>Column:</label></td><td><input type= "text" name="column"/></td>
                    </tr>
                    <tr>
                         <td><label for='method'>Method:</label></td>
                         <td><select size = '3' name='method'>
                              <option value='=' selected='selected'>=</option>
                              <option value='equal'>equal</option>
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
                              <option value='<'><</option>
                              <option value='less'>Less Than</option>
                              <option value='<='><=</option>
                              <option value='like'>Like</option>
                              <option value='not like'>Not Like</option>
                              
                         </select></td>
                    </tr>
                    <tr>
                         <td><label for='colvalue'>Column Value:</label></td><td><input type= "text" name="colvalue"/></td>
                    </tr>
                    <tr>
                         <td><input type="hidden" name="action" value="select1" /></td>
                    </tr>
                    <tr>
                         <td><input type="submit" /></td><td><input type="reset" /></td>
                    </tr>
               </table>
         </form>
     </body>
</html>