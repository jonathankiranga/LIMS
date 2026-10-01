USE [HRpayroll]
GO
/****** Object:  UserDefinedFunction [dbo].[billvalue]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE FUNCTION [dbo].[billvalue](@account varchar(20),@waterunits int)

            RETURNS NUMERIC(10,2)

            AS

            begin

            Declare @conntype char(3),@no_of_users int,@qow int,@minimumtarrif numeric(10,2),@paywater bit,@paysewer bit,@paybins bit
            declare @lowerlimit int,@upperlimit int,@pwater numeric(10,2),@psewer numeric(10,2),@pbins numeric(10,2),@sum numeric(10,2)

            DECLARE curcustomers CURSOR FOR SELECT  mb.conntype,mb.no_of_users ,mb.qow ,ct.minimumtarrif ,ct.water, ct.sewer ,ct.bins
            FROM [billingsmaster] mb join connectiontypes ct on mb.conntype=ct.code where mb.[accountcode] = @account

            OPEN curcustomers
            FETCH NEXT FROM curcustomers INTO @conntype,@no_of_users,@qow,@minimumtarrif,@paywater,@paysewer,@paybins
            WHILE @@FETCH_STATUS = 0
            BEGIN

                        DECLARE cursortarrif CURSOR FOR SELECT [fromcubids],[tocubids],[waterrate],[sewerrate],[binsrate]
                        FROM  [connectiontarrifs] where [connectypecode]=@conntype order by [rownumber] asc;

                       OPEN cursortarrif
                       FETCH NEXT FROM cursortarrif INTO @lowerlimit,@upperlimit,@pwater,@psewer,@pbins
                       WHILE @@FETCH_STATUS = 0
                       BEGIN


                       if(@waterunits <= @upperlimit)
                       begin
                            set @sum = @sum + ((@waterunits - @lowerlimit) * (isnull(@pwater,0) + isnull(@psewer,0) + isnull(@pbins,0)) )
                            set @waterunits = 0
                            break;
                       end
                       else
                       begin
                          set @sum = @sum + ((@upperlimit - @lowerlimit) *  (isnull(@pwater,0) + isnull(@psewer,0) + isnull(@pbins,0)))
                          set @waterunits = @waterunits - @lowerlimit
                       end

                       FETCH NEXT FROM cursortarrif INTO @lowerlimit,@upperlimit,@pwater,@psewer,@pbins
                       END

                       CLOSE cursortarrif
                       DEALLOCATE cursortarrif



               if(@minimumtarrif > @sum or @sum is null )
                begin
                    set @sum = @minimumtarrif
                end



            FETCH NEXT FROM curcustomers INTO @conntype,@no_of_users ,@qow ,@minimumtarrif ,@paywater ,@paysewer ,@paybins
            END

CLOSE curcustomers
DEALLOCATE curcustomers


return @sum

END



GO
/****** Object:  UserDefinedFunction [dbo].[deductloan]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE FUNCTION [dbo].[deductloan] (@loanindex int,@payroll_id int)
RETURNS float
AS
BEGIN
	
declare @loanbalance float,@instalment  float,@totaltodeduct  float

DECLARE knowloanbalance CURSOR FOR 
 select 
  prlstaffloans.principal-isnull(sum(prlloantrans.amount),0),
  prlstaffloans.instalment
 from prlstaffloans 
 left JOIN prlloantrans on prlstaffloans.loanindex = prlloantrans.loanindex and prlloantrans.payroll_id <= @payroll_id 
 Left JOIN prlmrollperiods on  prlmrollperiods.[pkey] = prlloantrans.payroll_id 
 where prlstaffloans.loanindex=@loanindex  
 and [prlstaffloans].[startdate]  <= getdate() 
 
 group by prlstaffloans.instalment,prlstaffloans.principal

set @totaltodeduct=0

OPEN knowloanbalance
FETCH NEXT FROM knowloanbalance INTO @loanbalance,@instalment
WHILE @@FETCH_STATUS = 0
BEGIN
/*
if(@loanbalance > @instalment)
set @totaltodeduct= @totaltodeduct + @instalment
else
begin
   set @totaltodeduct= @totaltodeduct + @loanbalance
end
*/
set   @loanbalance = @loanbalance

FETCH NEXT FROM knowloanbalance INTO @loanbalance,@instalment
END 

CLOSE knowloanbalance
DEALLOCATE knowloanbalance

-- Return the result of the function
RETURN  @loanbalance

END

GO
/****** Object:  UserDefinedFunction [dbo].[getindividualrelief]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE FUNCTION [dbo].[getindividualrelief](@pfno varchar(20),@payrollid int,@rtype bit)
RETURNS numeric(10,2) 
AS 
begin  

Declare @reliefamount  numeric(10,2),@reliefaspercent  numeric(10,2),@maxrelief numeric(10,2),@reliefvalue numeric(10,2)
Declare @sum numeric(10,2),@lowerlimit numeric(10,2),@upperlimit numeric(10,2),@percentageamount numeric(10,2)
Declare @taxableamount  numeric(10,2),@ceiling  numeric(10,2), @returnsum numeric(10,2),@basicpay numeric(10,2),
@hastable bit,@Tbasicpay numeric(10,2),@pens_tax int , @amountofpension numeric(10,2),@prodcode int
	
set @sum = 0
set @reliefvalue=0


DECLARE taxcursor CURSOR FOR select [code],[hastable],[pens_tax] FROM  prlproducts where pens_tax=1 
OPEN taxcursor
FETCH NEXT FROM taxcursor INTO @prodcode,@hastable,@pens_tax 
WHILE @@FETCH_STATUS = 0
BEGIN
-----------------this is the paye loop curcor

select @basicpay = (isnull(pt.basicpay,0) + isnull(pt.allowances,0) + isnull(pt.overtime,0)) ,@amountofpension = isnull(pt.pension,0) FROM  prlpayroltransfile  pt  where pt.payroll_id = @payrollid and  pt.pfno = @pfno
select @Tbasicpay = (@basicpay - isnull(@amountofpension,0))

-- get the relief INSERT INTO [prlreliefs]
           

DECLARE massupdate CURSOR FOR select [reliefamount],[reliefaspercent],[maxrelief] from prlreliefs where [productcode]= @prodcode and [type] = @rtype
OPEN massupdate
FETCH NEXT FROM massupdate INTO @reliefamount,@reliefaspercent,@maxrelief
WHILE @@FETCH_STATUS = 0
BEGIN

if(@reliefamount > 0 and @reliefamount is not null)
begin
	set @reliefvalue = @reliefvalue+ @reliefamount;
end

if(@reliefaspercent is not null and @reliefaspercent>0)
begin
    set @reliefvalue = @reliefvalue + (@Tbasicpay *(@reliefaspercent * 0.01))

	if(@maxrelief is not null and @maxrelief > 0)
	begin 
		if(@reliefvalue > @maxrelief)
			begin
				set @reliefvalue = @reliefvalue + @maxrelief
			end
	end
end


FETCH NEXT FROM massupdate INTO @reliefamount,@reliefaspercent,@maxrelief
END

CLOSE massupdate
DEALLOCATE massupdate

---- return values
FETCH NEXT FROM taxcursor INTO @prodcode,@hastable,@pens_tax
END

CLOSE taxcursor
DEALLOCATE taxcursor

return isnull(@reliefvalue,0)

end



GO
/****** Object:  UserDefinedFunction [dbo].[getnextno]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE FUNCTION [dbo].[getnextno](@typid int)
            RETURNS varchar(10)
            AS
            begin

            Declare @nos int

            select @nos = typeno from systypes_1 where [typeid] = @typid ;

            return (@nos)

            end



GO
/****** Object:  UserDefinedFunction [dbo].[getpayrollvalue]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE FUNCTION [dbo].[getpayrollvalue](@pfno varchar(20),@prodcode int,@pid int)
RETURNS float
AS 
begin  

Declare @reliefamount  float,@reliefaspercent  float,@maxrelief float,@reliefvalue float
Declare @sum float,@lowerlimit float,@upperlimit float,@percentageamount float
Declare @taxableamount  float,@ceiling  float, @returnsum float,@basicpay float
Declare @hastable int,@Tbasicpay float,@pens_tax int , @amountofpension float,@noncashbenefits float
Declare @totalallowances  float,@overtime   float,@otherReliefs float ,@minimunPension float

select @hastable =[hastable],@pens_tax =[pens_tax] FROM  prlproducts where prlproducts.code = @prodcode 
select @noncashbenefits =isnull( sum([amount]),0) from prlmatrix join prlproducts on prlmatrix.prodid=prlproducts.code  where prlmatrix.[pfno]=@pfno and prlproducts.pens_tax=3
select @amountofpension =isnull( sum([amount]),0) from prlmatrix join prlproducts on prlmatrix.prodid=prlproducts.code  where prlmatrix.[pfno]=@pfno and prlproducts.pens_tax=2
select @amountofpension = @amountofpension + isnull(sum([instalment]),0)  from [prlstaffloans] join prlproducts on [prlstaffloans].deductcode=prlproducts.code
where prlproducts.[deduction]=0 and prlproducts.pens_tax=2 and [prlstaffloans].[pfno]=@pfno and [prlstaffloans].[saving]=1

select @totalallowances = isnull(sum([amount]),0) from prlmatrix join prlproducts on prlmatrix.prodid=prlproducts.code  
where prlmatrix.[pfno]=@pfno and prlproducts.pens_tax = 0 and prlproducts.deduction=1 and prlmatrix.posted is null

select @minimunPension = iif((@amountofpension>20000),20000,@amountofpension)

select @basicpay = isnull(pm.basicpay,0),@otherReliefs=isnull(pm.[insurancerelief],0)  from prlemployeemaster  pm where  pm.pf_no = @pfno

select @overtime = (Sum(prltimesheet.noofhours) - sum(prltimesheet.recommended)) * (prlemployeemaster.basicpay/prlemployeemaster.noofhrsperday) 
from prltimesheet join prlemployeemaster on prltimesheet.pfno=prlemployeemaster.pf_no join prlmrollperiods on prltimesheet.date  
between prlmrollperiods.fromdate and prlmrollperiods.todate and prlmrollperiods.[pkey] = @pid 
where prltimesheet.pfno = @pfno and prlemployeemaster.basicpay>0 and prlemployeemaster.noofhrsperday>0
group by prlemployeemaster.basicpay,prlemployeemaster.noofhrsperday
     

if(@pens_tax=1)
begin
	set @Tbasicpay = (@basicpay + @totalallowances + @noncashbenefits + isnull(@overtime,0)) - isnull(@minimunPension,0)
end
else
	begin
		set @Tbasicpay = @basicpay + @totalallowances + isnull(@overtime,0)
	end


set @sum=0;


if(@hastable>0)
begin 
		--- these deductions or allowances use tables
					
if(@hastable=2) 
begin

					DECLARE massupdate CURSOR FOR 
					select @Tbasicpay ,
					prlitemaintenace.lowerlimit, 
					prlitemaintenace.upperlimit,
					prlitemaintenace.percentageamount, 
					isnull(prlitemaintenace.[ceiling],0) as [ceiling] 
					from prlitemaintenace 
					where code=@prodcode 
					order by taxband asc;

					OPEN massupdate
					FETCH NEXT FROM massupdate INTO @Tbasicpay,@lowerlimit,@upperlimit,@percentageamount,@ceiling
					WHILE @@FETCH_STATUS = 0  
					BEGIN

							if(@Tbasicpay between @lowerlimit and  @upperlimit)
							begin 
								set @sum = @sum + (@Tbasicpay - (@lowerlimit-1)) * (@percentageamount * 0.01)
								break;
							end
							else
							begin
							   if(@Tbasicpay >  @upperlimit) begin
							    set @sum = @sum +(@upperlimit - (@lowerlimit-1)) *  (@percentageamount * 0.01)
							  end
							end
				

					FETCH NEXT FROM massupdate INTO @Tbasicpay,@lowerlimit,@upperlimit,@percentageamount,@ceiling
					END

					CLOSE massupdate
					DEALLOCATE massupdate
end

if(@hastable=1) 
begin

					DECLARE massupdate CURSOR FOR 
					select prlitemaintenace.lowerlimit, 
					prlitemaintenace.upperlimit ,
					prlitemaintenace.[ceiling]  
					from prlitemaintenace where code=@prodcode and 
					(@Tbasicpay between prlitemaintenace.lowerlimit and prlitemaintenace.upperlimit)
					order by taxband asc;

					OPEN massupdate
					FETCH NEXT FROM massupdate INTO @lowerlimit,@upperlimit,@ceiling
					WHILE @@FETCH_STATUS = 0  
					BEGIN
					
						set @sum = @ceiling	
										   
					FETCH NEXT FROM massupdate INTO @lowerlimit,@upperlimit,@ceiling
					END

					CLOSE massupdate
					DEALLOCATE massupdate
end




end
else
begin
--- standing deductions
--- these deductions or allowances use tables
					
 
						DECLARE massupdate CURSOR FOR select prlitemaintenace.taxableamount, prlitemaintenace.[ceiling] ,
						 prlitemaintenace.percentageamount from prlitemaintenace where code=@prodcode ;

						OPEN massupdate
						FETCH NEXT FROM massupdate INTO @taxableamount,@ceiling,@percentageamount
						WHILE @@FETCH_STATUS = 0
						BEGIN

						if(@percentageamount is not null and @percentageamount>0)
						begin
							set @sum = @Tbasicpay * round((@percentageamount * 0.01),2)

							if(@ceiling is not null and @ceiling>0)
							begin
								if(@sum>@ceiling) 
								begin 
									set @sum=@ceiling
								end
							end
						end
						else
							begin
							set @sum = @taxableamount
							end


						FETCH NEXT FROM massupdate INTO @taxableamount,@ceiling,@percentageamount
						END

						CLOSE massupdate
						DEALLOCATE massupdate

end

-- get the relief INSERT INTO [prlreliefs]
           
		   
set @reliefvalue=0
DECLARE massupdate CURSOR FOR select [reliefamount],[reliefaspercent],[maxrelief] from prlreliefs where [productcode]= @prodcode
OPEN massupdate
FETCH NEXT FROM massupdate INTO @reliefamount,@reliefaspercent,@maxrelief
WHILE @@FETCH_STATUS = 0
BEGIN

if(@reliefamount>0 and @reliefamount is not null)
begin
	set @reliefvalue = @reliefamount;
end

if(@reliefaspercent is not null and @reliefaspercent>0)
begin
    set @reliefvalue = @Tbasicpay * (@reliefaspercent  * 0.01)

	if(@maxrelief is not null and @maxrelief>0)
	begin 
		if(@reliefvalue>@maxrelief)
			begin
				set @reliefvalue=@maxrelief
			end
	end
end


FETCH NEXT FROM massupdate INTO @reliefamount,@reliefaspercent,@maxrelief
END

CLOSE massupdate
DEALLOCATE massupdate

---- return values
if(@pens_tax=1)
begin
if(@sum > isnull(@reliefvalue,0) )
begin
	set @returnsum = @sum - (isnull(@reliefvalue,0)+@otherReliefs)
end
else
begin
    set @returnsum = 0
end
end
else
begin
		set @returnsum = @sum 
 end

return @returnsum

end


GO
/****** Object:  UserDefinedFunction [dbo].[getreliefvalue]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE FUNCTION [dbo].[getreliefvalue](@pfno varchar(20),@prodcode int,@payrollid int)
RETURNS numeric(10,2) 
AS 
begin  

Declare @reliefamount  numeric(10,2),@reliefaspercent  numeric(10,2),@maxrelief numeric(10,2),@reliefvalue numeric(10,2)

Declare @sum numeric(10,2),@lowerlimit numeric(10,2),@upperlimit numeric(10,2),@percentageamount numeric(10,2)
Declare @taxableamount  numeric(10,2),@ceiling  numeric(10,2), @returnsum numeric(10,2),@basicpay numeric(10,2),@hastable bit,@Tbasicpay numeric(10,2),@pens_tax int , @amountofpension numeric(10,2)
	
select @hastable =[hastable],@pens_tax =[pens_tax] FROM  prlproducts where prlproducts.code = @prodcode 

select @basicpay = (isnull(pt.basicpay,0) + isnull(pt.allowances,0) + isnull(pt.overtime,0)) ,@amountofpension = isnull(pt.pension,0) FROM  prlpayroltransfile  pt  where pt.payroll_id = @payrollid and  pt.pfno = @pfno


if(@pens_tax=1)
begin
	set @Tbasicpay = (@basicpay - isnull(@amountofpension,0))
end
else
	begin
		set @Tbasicpay = @basicpay 
	end


set @sum=0;


-- get the relief INSERT INTO [prlreliefs]
           

DECLARE massupdate CURSOR FOR select [reliefamount],[reliefaspercent],[maxrelief] 
from prlreliefs where [productcode]= @prodcode

set @reliefvalue=0

OPEN massupdate
FETCH NEXT FROM massupdate INTO @reliefamount,@reliefaspercent,@maxrelief
WHILE @@FETCH_STATUS = 0
BEGIN

if(@reliefamount>0 and @reliefamount is not null)
begin
	set @reliefvalue = @reliefamount;
end

if(@reliefaspercent is not null and @reliefaspercent>0)
begin
    set @reliefvalue = @Tbasicpay * (@reliefaspercent  * 0.01)

	if(@maxrelief is not null and @maxrelief>0)
	begin 
		if(@reliefvalue>@maxrelief)
			begin
				set @reliefvalue=@maxrelief
			end
	end
end


FETCH NEXT FROM massupdate INTO @reliefamount,@reliefaspercent,@maxrelief
END

CLOSE massupdate
DEALLOCATE massupdate

---- return values


return isnull(@reliefvalue,0)

end



GO
/****** Object:  UserDefinedFunction [dbo].[now]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE FUNCTION [dbo].[now]()
RETURNS datetime 
AS 
begin  
  RETURN getdate()
end



GO
/****** Object:  UserDefinedFunction [dbo].[QUARTER]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
create FUNCTION [dbo].[QUARTER](@datetime datetime)
RETURNS int 
AS 
begin  
  declare @weeks int

   set @weeks = DATEPART(qq,@datetime) 

  RETURN @weeks
end



GO
/****** Object:  UserDefinedFunction [dbo].[TO_DAYS]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE FUNCTION [dbo].[TO_DAYS](@datetime datetime)
RETURNS int 
AS 
begin  
  declare @days int

   set @days = DATEDIFF(day,'1900-01-01',@datetime) 

  RETURN @days
end



GO
/****** Object:  UserDefinedFunction [dbo].[WEEKOFYEAR]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE FUNCTION [dbo].[WEEKOFYEAR](@datetime datetime)
RETURNS int 
AS 
begin  
  declare @weeks int

   set @weeks = DATEPART (WW,@datetime) 

  RETURN @weeks
end



GO
/****** Object:  Table [dbo].[auditprlmatrix]    Script Date: 07/04/2026 11:45:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[auditprlmatrix](
	[userid] [varchar](50) NULL,
	[posted] [datetime] NULL,
	[pfno] [char](20) NOT NULL,
	[prodid] [int] NOT NULL,
	[name] [varchar](50) NOT NULL,
	[amount] [decimal](10, 2) NOT NULL,
	[employeramount] [decimal](10, 2) NULL,
	[deduction] [bit] NOT NULL,
	[nonrecuring] [bit] NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[audittrail]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[audittrail](
	[transactiondate] [datetime] NOT NULL,
	[userid] [varchar](20) NOT NULL,
	[querystring] [varchar](max) NULL
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[companies]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[companies](
	[coycode] [int] NOT NULL,
	[coyname] [varchar](50) NOT NULL,
	[gstno] [varchar](20) NOT NULL,
	[companynumber] [varchar](20) NOT NULL,
	[regoffice1] [varchar](40) NOT NULL,
	[regoffice2] [varchar](40) NOT NULL,
	[regoffice3] [varchar](40) NOT NULL,
	[regoffice4] [varchar](40) NOT NULL,
	[regoffice5] [varchar](20) NOT NULL,
	[regoffice6] [varchar](15) NOT NULL,
	[telephone] [varchar](25) NOT NULL,
	[fax] [varchar](25) NOT NULL,
	[email] [varchar](55) NOT NULL,
	[currencydefault] [varchar](4) NOT NULL,
	[debtorsact] [varchar](20) NOT NULL,
	[pytdiscountact] [varchar](20) NOT NULL,
	[creditorsact] [varchar](20) NOT NULL,
	[payrollact] [varchar](20) NOT NULL,
	[grnact] [varchar](20) NOT NULL,
	[exchangediffact] [varchar](20) NOT NULL,
	[purchasesexchangediffact] [varchar](20) NOT NULL,
	[retainedearnings] [varchar](20) NOT NULL,
	[gllink_debtors] [smallint] NULL,
	[gllink_creditors] [smallint] NULL,
	[gllink_stock] [smallint] NULL,
	[freightact] [varchar](20) NOT NULL,
	[lastjournalno] [bigint] NOT NULL,
 CONSTRAINT [PK_companies_coycode] PRIMARY KEY CLUSTERED 
(
	[coycode] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[config]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[config](
	[confname] [varchar](35) NOT NULL,
	[confvalue] [varchar](max) NOT NULL,
 CONSTRAINT [PK_config_confname] PRIMARY KEY CLUSTERED 
(
	[confname] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[currencies]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[currencies](
	[currency] [char](20) NOT NULL,
	[currabrev] [char](3) NOT NULL,
	[country] [char](50) NOT NULL,
	[hundredsname] [char](15) NOT NULL,
	[decimalplaces] [smallint] NOT NULL,
	[rate] [float] NOT NULL,
	[webcart] [smallint] NOT NULL,
 CONSTRAINT [PK_currencies_currabrev] PRIMARY KEY CLUSTERED 
(
	[currabrev] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[Dayoftheweeks]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[Dayoftheweeks](
	[Dayoftheweek] [varchar](50) NOT NULL,
	[isworkingday] [bit] NULL,
 CONSTRAINT [IX_Dayoftheweek] UNIQUE NONCLUSTERED 
(
	[Dayoftheweek] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[emailsettings]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[emailsettings](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[host] [varchar](30) NOT NULL,
	[port] [char](5) NOT NULL,
	[heloaddress] [varchar](20) NOT NULL,
	[username] [varchar](50) NULL,
	[password] [varchar](max) NULL,
	[timeout] [int] NULL,
	[companyname] [varchar](50) NULL,
	[auth] [smallint] NULL,
 CONSTRAINT [PK_emailsettings_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[employeebanks]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[employeebanks](
	[code] [varchar](20) NOT NULL,
	[bankname] [varchar](50) NULL,
	[firstitem] [bit] NULL,
	[pkey] [int] IDENTITY(1,1) NOT NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[employeebnkbranch]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[employeebnkbranch](
	[parentcode] [varchar](20) NOT NULL,
	[code] [varchar](20) NOT NULL,
	[bankbranch] [varchar](50) NULL,
	[address] [varchar](50) NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[geocode_param]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[geocode_param](
	[geocodeid] [smallint] IDENTITY(1,1) NOT NULL,
	[geocode_key] [varchar](200) NOT NULL,
	[center_long] [varchar](20) NOT NULL,
	[center_lat] [varchar](20) NOT NULL,
	[map_height] [varchar](10) NOT NULL,
	[map_width] [varchar](10) NOT NULL,
	[map_host] [varchar](50) NOT NULL,
 CONSTRAINT [PK_geocode_param_geocodeid] PRIMARY KEY CLUSTERED 
(
	[geocodeid] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[mailgroupdetails]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[mailgroupdetails](
	[groupname] [varchar](100) NOT NULL,
	[userid] [varchar](20) NOT NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[mailgroups]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[mailgroups](
	[id] [int] IDENTITY(5,1) NOT NULL,
	[groupname] [varchar](100) NOT NULL,
 CONSTRAINT [PK_mailgroups_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
 CONSTRAINT [mailgroups$mailgroups$groupname] UNIQUE NONCLUSTERED 
(
	[groupname] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[netprlproducts]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[netprlproducts](
	[code] [int] IDENTITY(1,1) NOT NULL,
	[description] [varchar](50) NOT NULL,
	[deduction] [bit] NOT NULL,
	[hastable] [int] NOT NULL,
	[employerfactor] [int] NULL,
	[pens_tax] [int] NULL,
	[membership_no] [varchar](150) NULL,
	[codenav] [varchar](20) NULL,
	[codenavd] [varchar](20) NULL,
	[Pagefilter] [varchar](50) NULL,
	[glaccountlink] [varchar](20) NULL,
	[bal_Pagefilter] [varchar](50) NULL,
	[bal_Glaccount] [varchar](20) NULL,
	[DimensionId] [int] NULL,
	[DimensionValue] [varchar](20) NULL,
 CONSTRAINT [PK_netprlproducts] PRIMARY KEY CLUSTERED 
(
	[code] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[periods]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[periods](
	[periodno] [smallint] NOT NULL,
	[lastdate_in_period] [datetime] NOT NULL,
 CONSTRAINT [PK_periods_periodno] PRIMARY KEY CLUSTERED 
(
	[periodno] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlauthorizers]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlauthorizers](
	[userdepartment] [int] NOT NULL,
	[positionapprover] [int] NOT NULL,
	[positionaplevel] [int] NOT NULL,
	[uniq] [uniqueidentifier] ROWGUIDCOL  NOT NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prldepartments]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prldepartments](
	[code] [int] IDENTITY(1,1) NOT NULL,
	[name] [varchar](50) NOT NULL,
	[hod] [char](20) NULL,
 CONSTRAINT [PK_prldepartments] PRIMARY KEY CLUSTERED 
(
	[code] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlemployeemaster]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlemployeemaster](
	[Inactive] [bit] NULL,
	[pf_no] [char](20) NOT NULL,
	[status] [varchar](10) NULL,
	[fname] [varchar](50) NOT NULL,
	[mname] [varchar](50) NOT NULL,
	[lname] [varchar](50) NOT NULL,
	[idno] [varchar](50) NULL,
	[pin_no] [nchar](20) NULL,
	[nssf_no] [nchar](20) NULL,
	[nhif_no] [nchar](20) NULL,
	[emppersonalno] [nchar](10) NULL,
	[dateob] [datetime] NULL,
	[dateemployed] [datetime] NULL,
	[dateterminated] [datetime] NULL,
	[freqcode] [char](1) NOT NULL,
	[branch] [char](10) NULL,
	[bankacno] [char](50) NULL,
	[bankcode2] [varchar](20) NULL,
	[bankcode] [varchar](20) NULL,
	[basicpay] [numeric](18, 2) NULL,
	[telno] [varchar](50) NULL,
	[email] [varchar](50) NULL,
	[noofhrsperday] [int] NULL,
	[position] [nchar](10) NULL,
	[salaryscale2] [nchar](20) NULL,
	[salaryscale] [nchar](20) NULL,
	[property1] [int] NULL,
	[property2] [int] NULL,
	[property3] [int] NULL,
	[property4] [int] NULL,
	[property5] [int] NULL,
	[property6] [int] NULL,
	[property7] [int] NULL,
	[property8] [int] NULL,
	[property9] [int] NULL,
	[property10] [int] NULL,
	[department] [int] NULL,
	[insurancerelief] [float] NULL,
	[mortagerelief] [float] NULL,
	[personalrelief] [float] NULL,
	[userid] [varchar](50) NULL,
	[userpassword] [varchar](max) NULL,
	[lastlogin] [date] NULL,
 CONSTRAINT [PK_prlemployeemaster] PRIMARY KEY CLUSTERED 
(
	[pf_no] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlestablishment]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlestablishment](
	[idcode] [char](10) NULL,
	[name] [varchar](50) NULL,
	[code] [int] IDENTITY(1,1) NOT NULL,
	[levels] [varchar](5) NULL,
	[county] [varchar](5) NULL,
	[address1] [varchar](50) NULL,
	[address2] [varchar](50) NULL,
	[address3] [varchar](50) NULL,
	[administrator] [varchar](20) NULL,
 CONSTRAINT [PK_prlestablishment] PRIMARY KEY CLUSTERED 
(
	[code] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlestablishment_details]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlestablishment_details](
	[establishcode] [char](10) NOT NULL,
	[positions] [nchar](10) NULL,
	[no] [int] NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlgeneralitems]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlgeneralitems](
	[type] [int] NOT NULL,
	[code] [int] IDENTITY(1,1) NOT NULL,
	[name] [varchar](50) NOT NULL,
 CONSTRAINT [PK_prlgeneralitems] PRIMARY KEY CLUSTERED 
(
	[code] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlhumanresource]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlhumanresource](
	[pfno] [char](20) NOT NULL,
	[prlgenitemscode] [int] NOT NULL,
	[prlgenitemsvalue] [varchar](50) NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlitemaintenace]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlitemaintenace](
	[code] [int] NOT NULL,
	[description] [varchar](30) NOT NULL,
	[taxband] [int] NULL,
	[onbasicpay] [bit] NULL,
	[taxableamount] [numeric](18, 2) NULL,
	[percentageamount] [numeric](18, 2) NULL,
	[lowerlimit] [numeric](18, 2) NULL,
	[upperlimit] [numeric](18, 2) NULL,
	[ceiling] [decimal](18, 0) NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prljobgroup]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prljobgroup](
	[name] [char](50) NOT NULL,
	[qualification] [char](50) NULL,
	[rowid] [int] IDENTITY(1,1) NOT NULL,
 CONSTRAINT [PK_prljobgroup] PRIMARY KEY CLUSTERED 
(
	[name] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlleaveapprovaltrans]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlleaveapprovaltrans](
	[docno] [char](10) NOT NULL,
	[userdepartment] [int] NOT NULL,
	[position] [int] NOT NULL,
	[authoritylevel] [int] NOT NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlloantrans]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlloantrans](
	[loanindex] [decimal](10, 0) NOT NULL,
	[amount] [float] NOT NULL,
	[interest] [float] NULL,
	[payroll_id] [int] NOT NULL,
	[pfno] [char](20) NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlmatrix]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlmatrix](
	[pfno] [char](20) NOT NULL,
	[prodid] [int] NOT NULL,
	[name] [varchar](50) NOT NULL,
	[amount] [float] NOT NULL,
	[employeramount] [float] NULL,
	[deduction] [bit] NOT NULL,
	[nonrecuring] [bit] NULL,
	[posted] [bit] NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlmrollperiods]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlmrollperiods](
	[pkey] [decimal](18, 0) IDENTITY(1,1) NOT NULL,
	[type] [int] NOT NULL,
	[fromdate] [datetime] NOT NULL,
	[todate] [datetime] NOT NULL,
	[open] [bit] NULL,
	[Printed] [bit] NULL,
	[printedby] [varchar](50) NULL,
 CONSTRAINT [PK_mrollperiods] PRIMARY KEY CLUSTERED 
(
	[pkey] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlnavwebserviceupdate]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlnavwebserviceupdate](
	[payrollid] [decimal](18, 0) NOT NULL,
	[code] [int] NOT NULL,
	[description] [varchar](50) NULL,
	[amount] [decimal](18, 2) NOT NULL,
	[pagetype] [varchar](50) NULL,
	[navcode] [varchar](20) NULL,
	[pagetype_balancing] [varchar](50) NULL,
	[navcode_balancing] [varchar](20) NULL,
	[posted] [datetime] NULL,
	[DimensionId] [int] NULL,
	[DimensionValue] [varchar](20) NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlpaydetailstransfile]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlpaydetailstransfile](
	[payroll_id] [decimal](18, 0) NOT NULL,
	[pfno] [char](20) NOT NULL,
	[code] [int] NOT NULL,
	[description] [varchar](50) NOT NULL,
	[amount] [float] NOT NULL,
	[employercontribution] [float] NULL,
	[deduction] [bit] NOT NULL,
	[reliefdeducted] [float] NULL,
	[non_cash_benefits] [float] NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlpayroltransfile]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlpayroltransfile](
	[pfno] [char](20) NOT NULL,
	[payroll_id] [bigint] NOT NULL,
	[basicpay] [float] NOT NULL,
	[allowances] [float] NULL,
	[deductions] [float] NULL,
	[non_cash_benefits] [float] NULL,
	[overtime] [float] NULL,
	[lateness_absent] [float] NULL,
	[pension] [float] NULL,
	[personalrelief] [float] NULL,
	[insurancerelief] [float] NULL,
	[rowid] [bigint] IDENTITY(1,1) NOT NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlpositions]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlpositions](
	[code] [int] IDENTITY(1,1) NOT NULL,
	[name] [varchar](50) NULL,
	[jobgroup] [char](50) NULL,
	[department] [int] NULL,
 CONSTRAINT [PK_prlpositions] PRIMARY KEY CLUSTERED 
(
	[code] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlproducts]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlproducts](
	[code] [int] IDENTITY(1,1) NOT NULL,
	[description] [varchar](50) NOT NULL,
	[deduction] [bit] NOT NULL,
	[hastable] [int] NOT NULL,
	[employerfactor] [int] NULL,
	[pens_tax] [int] NULL,
	[membership_no] [varchar](150) NULL,
	[codenav] [varchar](20) NULL,
	[codenavd] [varchar](20) NULL,
	[Pagefilter] [varchar](50) NULL,
	[glaccountlink] [varchar](20) NULL,
	[bal_Pagefilter] [varchar](50) NULL,
	[bal_Glaccount] [varchar](20) NULL,
	[DimensionId] [int] NULL,
	[DimensionValue] [varchar](20) NULL,
 CONSTRAINT [PK_prlproducts] PRIMARY KEY CLUSTERED 
(
	[code] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlqualifications]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlqualifications](
	[name] [char](50) NOT NULL,
	[code] [int] IDENTITY(1,1) NOT NULL,
 CONSTRAINT [PK_prlqualifications_1] PRIMARY KEY CLUSTERED 
(
	[name] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlreliefs]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlreliefs](
	[code] [int] IDENTITY(1,1) NOT NULL,
	[productcode] [int] NOT NULL,
	[name] [varchar](50) NOT NULL,
	[reliefamount] [decimal](18, 2) NULL,
	[reliefaspercent] [int] NULL,
	[maxrelief] [decimal](18, 2) NULL,
	[type] [bit] NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlsalaryscale]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlsalaryscale](
	[jbgroup] [char](50) NOT NULL,
	[code] [char](50) NOT NULL,
	[qualifications] [char](50) NULL,
	[min] [numeric](18, 2) NULL,
	[annual_inc] [numeric](10, 2) NULL,
	[max] [numeric](18, 2) NULL,
	[lastupdate_year] [int] NULL,
	[lastupdate] [smalldatetime] NULL
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlspecialdays]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlspecialdays](
	[name] [char](50) NOT NULL,
	[day] [int] NULL,
	[month] [int] NULL,
	[week] [int] NULL,
	[rowid] [int] IDENTITY(1,1) NOT NULL,
 CONSTRAINT [PK_prlspecialdays] PRIMARY KEY CLUSTERED 
(
	[name] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlstaffleavemaster]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlstaffleavemaster](
	[refno] [int] NOT NULL,
	[pfno] [char](20) NOT NULL,
	[year] [int] NOT NULL,
	[days] [int] NOT NULL,
 CONSTRAINT [PK_prlstaffleavemaster] PRIMARY KEY CLUSTERED 
(
	[refno] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlstaffleaveplanner]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlstaffleaveplanner](
	[refno] [char](10) NOT NULL,
	[pfno] [char](20) NOT NULL,
	[handover] [char](20) NOT NULL,
	[year] [int] NOT NULL,
	[leavedue] [datetime] NOT NULL,
	[leavend] [datetime] NOT NULL,
	[days] [int] NULL,
	[typeofleave] [int] NOT NULL,
	[status] [int] NOT NULL,
	[approvelevel] [int] NULL,
 CONSTRAINT [PK_prlstaffleaveplanner] PRIMARY KEY CLUSTERED 
(
	[refno] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prlstaffloans]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prlstaffloans](
	[loanindex] [decimal](10, 0) IDENTITY(1,1) NOT NULL,
	[pfno] [char](20) NOT NULL,
	[deductcode] [int] NOT NULL,
	[principal] [decimal](18, 2) NOT NULL,
	[instalment] [decimal](18, 2) NOT NULL,
	[interest] [decimal](18, 2) NULL,
	[startdate] [datetime] NOT NULL,
	[closed] [bit] NULL,
	[saving] [bit] NULL,
	[interesttype] [int] NULL,
	[openbalance] [float] NULL,
 CONSTRAINT [PK_prlstaffloans] PRIMARY KEY CLUSTERED 
(
	[loanindex] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prltimesheet]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prltimesheet](
	[pfno] [char](20) NOT NULL,
	[timein] [smalldatetime] NULL,
	[timeout] [smalldatetime] NULL,
	[ShouldLogIn] [bit] NOT NULL,
	[date] [datetime] NOT NULL,
	[period] [int] NOT NULL,
	[week]  AS (datepart(week,[date])),
	[year]  AS (datepart(year,[date])),
	[noofmin]  AS (datediff(minute,[timein],[timeout])) PERSISTED,
	[noofdays]  AS (datediff(day,[timein],[timeout])) PERSISTED,
	[noofhours]  AS (datediff(minute,[timein],[timeout])/(60)),
	[recommended] [int] NULL,
	[id] [int] IDENTITY(1,1) NOT NULL,
	[DidtheylogIn] [int] NULL,
 CONSTRAINT [IX_prltimesheet] UNIQUE NONCLUSTERED 
(
	[pfno] ASC,
	[date] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[prltypes]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[prltypes](
	[code] [int] IDENTITY(1,1) NOT NULL,
	[type] [varchar](30) NOT NULL,
	[setup] [bit] NOT NULL,
	[system] [bit] NULL,
 CONSTRAINT [PK_prltypes] PRIMARY KEY CLUSTERED 
(
	[code] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[scripts]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[scripts](
	[script] [varchar](78) NOT NULL,
	[pagesecurity] [int] NOT NULL,
	[description] [varchar](max) NOT NULL,
 CONSTRAINT [PK_scripts_script] PRIMARY KEY CLUSTERED 
(
	[script] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[securitygroups]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[securitygroups](
	[secroleid] [int] NOT NULL,
	[tokenid] [int] NOT NULL,
 CONSTRAINT [PK_securitygroups_secroleid] PRIMARY KEY CLUSTERED 
(
	[secroleid] ASC,
	[tokenid] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[securityroles]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[securityroles](
	[secroleid] [int] IDENTITY(1,1) NOT NULL,
	[secrolename] [varchar](max) NOT NULL,
 CONSTRAINT [PK_securityroles_secroleid] PRIMARY KEY CLUSTERED 
(
	[secroleid] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[securitytokens]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[securitytokens](
	[tokenid] [int] NOT NULL,
	[tokenname] [varchar](max) NOT NULL,
 CONSTRAINT [PK_securitytokens_tokenid] PRIMARY KEY CLUSTERED 
(
	[tokenid] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[systypes_1]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[systypes_1](
	[typeid] [smallint] NOT NULL,
	[typename] [nchar](50) NOT NULL,
	[typeno] [int] NOT NULL,
	[prefix] [char](10) NULL,
 CONSTRAINT [PK_systypes_1_typeid] PRIMARY KEY CLUSTERED 
(
	[typeid] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[www_users]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[www_users](
	[userid] [varchar](20) NOT NULL,
	[password] [varchar](max) NOT NULL,
	[realname] [varchar](35) NOT NULL,
	[payrollid] [char](20) NULL,
	[phone] [varchar](30) NOT NULL,
	[email] [varchar](55) NULL,
	[fullaccess] [int] NOT NULL,
	[lastvisitdate] [datetime] NULL,
	[branchcode] [varchar](10) NOT NULL,
	[pagesize] [varchar](20) NOT NULL,
	[modulesallowed] [varchar](40) NOT NULL,
	[blocked] [smallint] NOT NULL,
	[displayrecordsmax] [int] NOT NULL,
	[theme] [varchar](30) NOT NULL,
	[language] [varchar](10) NOT NULL,
	[pdflanguage] [smallint] NOT NULL,
	[departcode] [int] NULL,
 CONSTRAINT [PK_www_users_userid] PRIMARY KEY CLUSTERED 
(
	[userid] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
ALTER TABLE [dbo].[audittrail] ADD  CONSTRAINT [DF__audittrai__trans__07F6335A]  DEFAULT (getdate()) FOR [transactiondate]
GO
ALTER TABLE [dbo].[companies] ADD  DEFAULT ((1)) FOR [coycode]
GO
ALTER TABLE [dbo].[companies] ADD  DEFAULT ((1)) FOR [gllink_debtors]
GO
ALTER TABLE [dbo].[companies] ADD  DEFAULT ((1)) FOR [gllink_creditors]
GO
ALTER TABLE [dbo].[companies] ADD  DEFAULT ((1)) FOR [gllink_stock]
GO
ALTER TABLE [dbo].[currencies] ADD  DEFAULT ((2)) FOR [decimalplaces]
GO
ALTER TABLE [dbo].[currencies] ADD  DEFAULT ((1)) FOR [rate]
GO
ALTER TABLE [dbo].[currencies] ADD  DEFAULT ((1)) FOR [webcart]
GO
ALTER TABLE [dbo].[emailsettings] ADD  CONSTRAINT [DF__emailsett__usern__778AC167]  DEFAULT (NULL) FOR [username]
GO
ALTER TABLE [dbo].[emailsettings] ADD  CONSTRAINT [DF__emailsett__passw__787EE5A0]  DEFAULT (NULL) FOR [password]
GO
ALTER TABLE [dbo].[emailsettings] ADD  CONSTRAINT [DF__emailsett__timeo__797309D9]  DEFAULT ((5)) FOR [timeout]
GO
ALTER TABLE [dbo].[emailsettings] ADD  CONSTRAINT [DF__emailsett__compa__7A672E12]  DEFAULT (NULL) FOR [companyname]
GO
ALTER TABLE [dbo].[emailsettings] ADD  CONSTRAINT [DF__emailsetti__auth__7B5B524B]  DEFAULT ((0)) FOR [auth]
GO
ALTER TABLE [dbo].[periods] ADD  DEFAULT ((0)) FOR [periodno]
GO
ALTER TABLE [dbo].[periods] ADD  DEFAULT (getdate()) FOR [lastdate_in_period]
GO
ALTER TABLE [dbo].[prlauthorizers] ADD  CONSTRAINT [DF_prlauthorizers_positionaplevel]  DEFAULT ((9)) FOR [positionaplevel]
GO
ALTER TABLE [dbo].[prlauthorizers] ADD  CONSTRAINT [DF_prlauthorizers_uniq]  DEFAULT (newid()) FOR [uniq]
GO
ALTER TABLE [dbo].[prlmrollperiods] ADD  CONSTRAINT [DF_mrollperiods_open]  DEFAULT ((0)) FOR [open]
GO
ALTER TABLE [dbo].[scripts] ADD  DEFAULT ((1)) FOR [pagesecurity]
GO
ALTER TABLE [dbo].[securitygroups] ADD  DEFAULT ((0)) FOR [secroleid]
GO
ALTER TABLE [dbo].[securitygroups] ADD  DEFAULT ((0)) FOR [tokenid]
GO
ALTER TABLE [dbo].[securitytokens] ADD  DEFAULT ((0)) FOR [tokenid]
GO
ALTER TABLE [dbo].[systypes_1] ADD  DEFAULT ((0)) FOR [typeid]
GO
ALTER TABLE [dbo].[systypes_1] ADD  DEFAULT (N'') FOR [typename]
GO
ALTER TABLE [dbo].[systypes_1] ADD  DEFAULT ((1)) FOR [typeno]
GO
ALTER TABLE [dbo].[www_users] ADD  CONSTRAINT [DF__www_users__email__5E74FADA]  DEFAULT (NULL) FOR [email]
GO
ALTER TABLE [dbo].[www_users] ADD  CONSTRAINT [DF__www_users__fulla__5F691F13]  DEFAULT ((1)) FOR [fullaccess]
GO
ALTER TABLE [dbo].[www_users] ADD  CONSTRAINT [DF__www_users__lastv__61516785]  DEFAULT (NULL) FOR [lastvisitdate]
GO
ALTER TABLE [dbo].[www_users] ADD  CONSTRAINT [DF__www_users__block__62458BBE]  DEFAULT ((0)) FOR [blocked]
GO
ALTER TABLE [dbo].[www_users] ADD  CONSTRAINT [DF__www_users__displ__6339AFF7]  DEFAULT ((0)) FOR [displayrecordsmax]
GO
ALTER TABLE [dbo].[www_users] ADD  CONSTRAINT [DF__www_users__pdfla__642DD430]  DEFAULT ((0)) FOR [pdflanguage]
GO
ALTER TABLE [dbo].[prlauthorizers]  WITH CHECK ADD  CONSTRAINT [FK_prlauthorizers_prldepartments] FOREIGN KEY([userdepartment])
REFERENCES [dbo].[prldepartments] ([code])
GO
ALTER TABLE [dbo].[prlauthorizers] CHECK CONSTRAINT [FK_prlauthorizers_prldepartments]
GO
ALTER TABLE [dbo].[prlauthorizers]  WITH CHECK ADD  CONSTRAINT [FK_prlauthorizers_prlpositions] FOREIGN KEY([positionapprover])
REFERENCES [dbo].[prlpositions] ([code])
GO
ALTER TABLE [dbo].[prlauthorizers] CHECK CONSTRAINT [FK_prlauthorizers_prlpositions]
GO
ALTER TABLE [dbo].[prlgeneralitems]  WITH NOCHECK ADD  CONSTRAINT [FK_prlgeneralitems_prltypes] FOREIGN KEY([type])
REFERENCES [dbo].[prltypes] ([code])
GO
ALTER TABLE [dbo].[prlgeneralitems] CHECK CONSTRAINT [FK_prlgeneralitems_prltypes]
GO
ALTER TABLE [dbo].[prlhumanresource]  WITH NOCHECK ADD  CONSTRAINT [FK_prlhumanresource_prlemployeemaster] FOREIGN KEY([pfno])
REFERENCES [dbo].[prlemployeemaster] ([pf_no])
GO
ALTER TABLE [dbo].[prlhumanresource] CHECK CONSTRAINT [FK_prlhumanresource_prlemployeemaster]
GO
ALTER TABLE [dbo].[prljobgroup]  WITH CHECK ADD  CONSTRAINT [FK_prljobgroup_prlqualifications] FOREIGN KEY([qualification])
REFERENCES [dbo].[prlqualifications] ([name])
GO
ALTER TABLE [dbo].[prljobgroup] CHECK CONSTRAINT [FK_prljobgroup_prlqualifications]
GO
ALTER TABLE [dbo].[prlloantrans]  WITH NOCHECK ADD  CONSTRAINT [FK_prlloantrans_prlstaffloans] FOREIGN KEY([loanindex])
REFERENCES [dbo].[prlstaffloans] ([loanindex])
GO
ALTER TABLE [dbo].[prlloantrans] CHECK CONSTRAINT [FK_prlloantrans_prlstaffloans]
GO
ALTER TABLE [dbo].[prlmatrix]  WITH NOCHECK ADD  CONSTRAINT [FK_prlmatrix_prlemployeemaster] FOREIGN KEY([pfno])
REFERENCES [dbo].[prlemployeemaster] ([pf_no])
GO
ALTER TABLE [dbo].[prlmatrix] CHECK CONSTRAINT [FK_prlmatrix_prlemployeemaster]
GO
ALTER TABLE [dbo].[prlnavwebserviceupdate]  WITH CHECK ADD  CONSTRAINT [FK_prlnavwebserviceupdate_prlmrollperiods] FOREIGN KEY([payrollid])
REFERENCES [dbo].[prlmrollperiods] ([pkey])
GO
ALTER TABLE [dbo].[prlnavwebserviceupdate] CHECK CONSTRAINT [FK_prlnavwebserviceupdate_prlmrollperiods]
GO
ALTER TABLE [dbo].[prlpaydetailstransfile]  WITH NOCHECK ADD  CONSTRAINT [FK_prlpaydetailstransfile_prlemployeemaster] FOREIGN KEY([pfno])
REFERENCES [dbo].[prlemployeemaster] ([pf_no])
GO
ALTER TABLE [dbo].[prlpaydetailstransfile] CHECK CONSTRAINT [FK_prlpaydetailstransfile_prlemployeemaster]
GO
ALTER TABLE [dbo].[prlpayroltransfile]  WITH NOCHECK ADD  CONSTRAINT [FK_prlpayroltransfile_prlemployeemaster] FOREIGN KEY([pfno])
REFERENCES [dbo].[prlemployeemaster] ([pf_no])
GO
ALTER TABLE [dbo].[prlpayroltransfile] CHECK CONSTRAINT [FK_prlpayroltransfile_prlemployeemaster]
GO
ALTER TABLE [dbo].[prlpositions]  WITH CHECK ADD  CONSTRAINT [FK_prlpositions_prljobgroup] FOREIGN KEY([jobgroup])
REFERENCES [dbo].[prljobgroup] ([name])
GO
ALTER TABLE [dbo].[prlpositions] CHECK CONSTRAINT [FK_prlpositions_prljobgroup]
GO
ALTER TABLE [dbo].[prlreliefs]  WITH NOCHECK ADD  CONSTRAINT [FK_prlreliefs_prlproducts] FOREIGN KEY([productcode])
REFERENCES [dbo].[prlproducts] ([code])
GO
ALTER TABLE [dbo].[prlreliefs] CHECK CONSTRAINT [FK_prlreliefs_prlproducts]
GO
ALTER TABLE [dbo].[prlsalaryscale]  WITH CHECK ADD  CONSTRAINT [FK_prlsalaryscale_prljobgroup] FOREIGN KEY([jbgroup])
REFERENCES [dbo].[prljobgroup] ([name])
GO
ALTER TABLE [dbo].[prlsalaryscale] CHECK CONSTRAINT [FK_prlsalaryscale_prljobgroup]
GO
ALTER TABLE [dbo].[prlsalaryscale]  WITH CHECK ADD  CONSTRAINT [FK_prlsalaryscale_prlqualifications] FOREIGN KEY([qualifications])
REFERENCES [dbo].[prlqualifications] ([name])
GO
ALTER TABLE [dbo].[prlsalaryscale] CHECK CONSTRAINT [FK_prlsalaryscale_prlqualifications]
GO
ALTER TABLE [dbo].[prlstaffloans]  WITH NOCHECK ADD  CONSTRAINT [FK_prlstaffloans_prlproducts] FOREIGN KEY([deductcode])
REFERENCES [dbo].[prlproducts] ([code])
GO
ALTER TABLE [dbo].[prlstaffloans] CHECK CONSTRAINT [FK_prlstaffloans_prlproducts]
GO
ALTER TABLE [dbo].[prlstaffloans]  WITH NOCHECK ADD  CONSTRAINT [FK_prlstaffloans_prlstaffloans] FOREIGN KEY([loanindex])
REFERENCES [dbo].[prlstaffloans] ([loanindex])
GO
ALTER TABLE [dbo].[prlstaffloans] CHECK CONSTRAINT [FK_prlstaffloans_prlstaffloans]
GO
ALTER TABLE [dbo].[prltimesheet]  WITH CHECK ADD  CONSTRAINT [FK_prltimesheet_prlemployeemaster] FOREIGN KEY([pfno])
REFERENCES [dbo].[prlemployeemaster] ([pf_no])
GO
ALTER TABLE [dbo].[prltimesheet] CHECK CONSTRAINT [FK_prltimesheet_prlemployeemaster]
GO
ALTER TABLE [dbo].[securitygroups]  WITH NOCHECK ADD  CONSTRAINT [securitygroups$securitygroups$securitygroups_secroleid_fk] FOREIGN KEY([secroleid])
REFERENCES [dbo].[securityroles] ([secroleid])
GO
ALTER TABLE [dbo].[securitygroups] CHECK CONSTRAINT [securitygroups$securitygroups$securitygroups_secroleid_fk]
GO
ALTER TABLE [dbo].[securitygroups]  WITH NOCHECK ADD  CONSTRAINT [securitygroups$securitygroups$securitygroups_tokenid_fk] FOREIGN KEY([tokenid])
REFERENCES [dbo].[securitytokens] ([tokenid])
GO
ALTER TABLE [dbo].[securitygroups] CHECK CONSTRAINT [securitygroups$securitygroups$securitygroups_tokenid_fk]
GO
ALTER TABLE [dbo].[www_users]  WITH CHECK ADD  CONSTRAINT [FK_www_users_www_users] FOREIGN KEY([userid])
REFERENCES [dbo].[www_users] ([userid])
GO
ALTER TABLE [dbo].[www_users] CHECK CONSTRAINT [FK_www_users_www_users]
GO
/****** Object:  StoredProcedure [dbo].[approveleaveappliction]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[approveleaveappliction] AS BEGIN

DECLARE @leaveapplication Table (refno char(10),pfno char(20),handover char(20),year int,
leavedue datetime,leavend datetime,days int,typeofleave char(2),status int,
email varchar(50),appliedby varchar(50),names varchar(50),appliedby2 varchar(50),sub1 varchar(50));

insert into @leaveapplication
    SELECT prlstaffleaveplanner.[refno]
      ,prlstaffleaveplanner.[pfno]
      ,prlstaffleaveplanner.[handover]
      ,prlstaffleaveplanner.[year]
      ,prlstaffleaveplanner.[leavedue]
      ,prlstaffleaveplanner.[leavend]
      ,prlstaffleaveplanner.[days]
      ,prlstaffleaveplanner.[typeofleave]
      ,prlstaffleaveplanner.[status]
      ,prlemployeemaster.[email]
      ,prlemployeemaster.[fname] +' '+prlemployeemaster.[mname]+' '+prlemployeemaster.[lname]
      ,prlemployeemaster2.[fname] +' '+prlemployeemaster2.[mname]+' '+prlemployeemaster2.[lname]
      ,prlpositions.[name]
      ,prlpositions2.[name]
      from prlstaffleaveplanner
      left join prlemployeemaster on prlstaffleaveplanner.pfno=prlemployeemaster.pf_no
      left join prlemployeemaster prlemployeemaster2 on prlstaffleaveplanner.handover=prlemployeemaster2.pf_no
      left join prlpositions on prlemployeemaster.position=prlpositions.code
      left join prlpositions prlpositions2 on prlemployeemaster2.position=prlpositions2.code
       where prlstaffleaveplanner.[refno] in (Select T.[docno]  FROM [prlleaveapprovaltrans] T  
       join [prlemployeemaster] M on T.[position]=M.[position]  where  M.[pf_no]='PF001' )
      
       select * from @leaveapplication
END
GO
/****** Object:  StoredProcedure [dbo].[approveselectedleave]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[approveselectedleave] AS BEGIN

DECLARE @leaveapplication Table (refno varchar(10),pfno char(20),handover char(20),year int,
leavedue datetime,leavend datetime,days int,typeofleave char(2),status int,
email varchar(50),appliedby varchar(50),names varchar(50),appliedby2 varchar(50),sub1 varchar(50));

insert into @leaveapplication
    SELECT prlstaffleaveplanner.[refno]
      ,prlstaffleaveplanner.[pfno]
      ,prlstaffleaveplanner.[handover]
      ,prlstaffleaveplanner.[year]
      ,prlstaffleaveplanner.[leavedue]
      ,prlstaffleaveplanner.[leavend]
      ,prlstaffleaveplanner.[days]
      ,prlstaffleaveplanner.[typeofleave]
      ,prlstaffleaveplanner.[status]
      ,prlemployeemaster.[email]
      ,prlemployeemaster.[fname] +' '+prlemployeemaster.[mname]+' '+prlemployeemaster.[lname]
      ,prlemployeemaster2.[fname] +' '+prlemployeemaster2.[mname]+' '+prlemployeemaster2.[lname]
      ,prlpositions.[name]
      ,prlpositions2.[name]
      from prlstaffleaveplanner
      left join prlemployeemaster on prlstaffleaveplanner.pfno=prlemployeemaster.pf_no
      left join prlemployeemaster prlemployeemaster2 on prlstaffleaveplanner.handover=prlemployeemaster2.pf_no
      left join prlpositions on prlemployeemaster.position=prlpositions.code
      left join prlpositions prlpositions2 on prlemployeemaster2.position=prlpositions2.code
   where prlstaffleaveplanner.[pfno]='PF001'
       and prlstaffleaveplanner.refno='0095'

   select * from @leaveapplication

END

GO
/****** Object:  StoredProcedure [dbo].[billvaluedetails]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[billvaluedetails]   AS

Declare @conntype char(3),@no_of_users int,@qow int,@minimumtarrif numeric(10,2),@paywater bit,@paysewer bit,@paybins bit
declare @lowerlimit int,@upperlimit int,@pwater numeric(10,2),@psewer numeric(10,2),@pbins numeric(10,2)
Declare @sum numeric(10,2),@waterunits int,@unitprice numeric(10,2),@accountcode varchar(20)


DECLARE curcustomers CURSOR FOR SELECT
mb.conntype , mb.no_of_users , mb.qow , ct.minimumtarrif ,ct.water, ct.sewer ,ct.bins , billingtrans.water ,mb.accountcode
FROM [billingsmaster] mb
left outer join connectiontypes ct on mb.conntype = ct.code
left outer join billingtrans on mb.accountcode = billingtrans.accountno
and billingtrans.period ='3'


OPEN curcustomers
FETCH NEXT FROM curcustomers INTO @conntype , @no_of_users , @qow , @minimumtarrif , @paywater , @paysewer , @paybins,@waterunits,@accountcode
WHILE @@FETCH_STATUS = 0
BEGIN


           DECLARE cursortarrif CURSOR FOR SELECT [fromcubids],[tocubids],[waterrate],[sewerrate],[binsrate]  FROM  [connectiontarrifs] where [connectypecode]=@conntype order by [rownumber] asc;
           OPEN cursortarrif
           FETCH NEXT FROM cursortarrif INTO @lowerlimit,@upperlimit,@pwater,@psewer,@pbins
           WHILE @@FETCH_STATUS = 0
           BEGIN

            set @unitprice = (isnull(@pwater,0) + isnull(@psewer,0) + isnull(@pbins,0))

           if(@waterunits <= @upperlimit)
           begin

                insert into [billingtransdetails]([periodid],[accountno],[billno],[water],[price],[total])
                (SELECT  billingtrans.period, billingtrans.accountno, billingtrans.billno, @waterunits, @unitprice,(@unitprice * @waterunits)
                 from billingtrans where  billingtrans.period = '3')

                set @waterunits = 0
                break;
           end
           else
           begin

                set @waterunits = (@waterunits - @lowerlimit)

                insert into [billingtransdetails]([periodid],[accountno],[billno],[water],[price],[total])
                (SELECT  billingtrans.period, billingtrans.accountno, billingtrans.billno, @waterunits, @unitprice,(@unitprice * @waterunits)
                 from billingtrans where billingtrans.accountno = @accountcode
                 and  billingtrans.period = '3')

           end




           FETCH NEXT FROM cursortarrif INTO @lowerlimit,@upperlimit,@pwater,@psewer,@pbins
           END

           CLOSE cursortarrif
           DEALLOCATE cursortarrif



FETCH NEXT FROM curcustomers INTO @conntype,@no_of_users ,@qow ,@minimumtarrif ,@paywater ,@paysewer ,@paybins,@waterunits,@accountcode
END
CLOSE curcustomers
DEALLOCATE curcustomers



GO
/****** Object:  StoredProcedure [dbo].[Createwaterbillingsystem]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Author,,Name>
-- Create date: <Create Date,,>
-- Description:	<Description,,>
-- =============================================
CREATE PROCEDURE [dbo].[Createwaterbillingsystem] 
	
AS
BEGIN
	DECLARE @count int

    -- Insert statements for procedure here
	SELECT @count = count(*) from config where confname='Revenuestream' 
	if(@count>0)
	begin
			Delete from config where confname='Revenuestream' 
	end 
ELSE 
	BEGIN
			insert into config select 'Revenuestream',1
	END

END



GO
/****** Object:  StoredProcedure [dbo].[GetDOWdateofreturn]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[GetDOWdateofreturn](@checkdate smalldatetime, @outparam smalldatetime OUTPUT) 
    AS BEGIN 
    
    Declare @today smalldatetime, @days int 
     select @today = @checkdate
          
    Declare @DateName varchar(max),@CurrentDate smalldatetime, 
    @offday int, @offmonth int ,@LastWorkingDay smalldatetime
    
    select @LastWorkingDay=DATEADD (D,14,@today)

    Declare @workingdays table (namevar varchar(max),date datetime);

            with View_MONTH as
            (
               select cast(@checkdate as smalldatetime) as DateValue
               union all
               select DATEADD (D,1,DateValue) from View_MONTH where DATEADD (D,1,DateValue) <= @LastWorkingDay
            )

            insert into @workingdays select DATENAME(WEEKDAY,DateValue),DateValue from View_MONTH

         
            DECLARE mymonth CURSOR FOR SELECT namevar ,date  from @workingdays
            OPEN mymonth
            FETCH NEXT FROM mymonth INTO @DateName ,@CurrentDate
            WHILE @@FETCH_STATUS = 0
            BEGIN
                   Declare @isworkingday bit,@holiday varchar(50)
                   
                    SELECT @isworkingday=[isworkingday]  FROM [Dayoftheweeks] where [Dayoftheweek]=@DateName
                   
                    SELECT @holiday = [name] from [prlspecialdays] where day=datepart(d,@CurrentDate) 
                    and month=datepart(m,@CurrentDate)
                        
                    if(@isworkingday=1) 
                    begin
                         select @outparam = @CurrentDate
                       
                         if(@holiday is null)  
                         begin 
                              select @outparam = @CurrentDate 
                              BREAK
                         end
                          
                         
                    end
   
                FETCH NEXT FROM mymonth INTO @DateName ,@CurrentDate
            END
            
            CLOSE mymonth
            DEALLOCATE mymonth
            

  select @outparam
            

END
GO
/****** Object:  StoredProcedure [dbo].[getmasterroll]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[getmasterroll]
    AS
    BEGIN

    DECLARE @masterroll Table (A_10 float,A_17 float,A_20 float,D_1 float,D_2 float,D_3 float, pfno char(20),
        names varchar(50),
        basicpay float,
        Overtime float,
        Manhrlost float,
        Loans float
     );   insert into @masterroll  select  pfle_10.amount  ,  pfle_17.amount  ,  pfle_20.amount  ,  pfle_1.amount  ,  pfle_2.amount  ,  pfle_3.amount  ,  prlpayroltransfile.pfno ,
    Rtrim(prlemployeemaster.fname) +' '+ Rtrim(prlemployeemaster.mname)+' '+ Rtrim(prlemployeemaster.lname),
    isnull(prlpayroltransfile.basicpay,0) ,
    isnull(prlpayroltransfile.[overtime],0) ,
    isnull(prlpayroltransfile.[lateness_absent],0) ,
    (select 
    isnull(sum([prlloantrans].[amount]),0) + isnull(sum([prlloantrans].[interest]),0) 
    from prlloantrans 
    where prlloantrans.[pfno]=prlpayroltransfile.pfno and prlloantrans.[payroll_id] ='19') as totalloan
    from  prlpayroltransfile
    left join prlemployeemaster on prlpayroltransfile.[pfno]=prlemployeemaster.pf_no
    left join prlloantrans on  prlpayroltransfile.[pfno]=prlloantrans.[pfno]  left join prlpaydetailstransfile  pfle_10 on
         prlpayroltransfile.[pfno]=pfle_10.[pfno]
          and pfle_10.code='10'
          and pfle_10.[payroll_id] ='19' left join prlpaydetailstransfile  pfle_17 on
         prlpayroltransfile.[pfno]=pfle_17.[pfno]
          and pfle_17.code='17'
          and pfle_17.[payroll_id] ='19' left join prlpaydetailstransfile  pfle_20 on
         prlpayroltransfile.[pfno]=pfle_20.[pfno]
          and pfle_20.code='20'
          and pfle_20.[payroll_id] ='19' left join prlpaydetailstransfile  pfle_1 on
         prlpayroltransfile.[pfno]=pfle_1.[pfno]
          and pfle_1.code='1'
          and pfle_1.[payroll_id] ='19' left join prlpaydetailstransfile  pfle_2 on
         prlpayroltransfile.[pfno]=pfle_2.[pfno]
          and pfle_2.code='2'
          and pfle_2.[payroll_id] ='19' left join prlpaydetailstransfile  pfle_3 on
         prlpayroltransfile.[pfno]=pfle_3.[pfno]
          and pfle_3.code='3'
          and pfle_3.[payroll_id] ='19' where  prlpayroltransfile.[payroll_id]='19' Group by   pfle_10.amount,  pfle_17.amount,  pfle_20.amount,  pfle_1.amount,  pfle_2.amount,  pfle_3.amount, prlpayroltransfile.pfno ,
    prlpayroltransfile.basicpay ,
    prlpayroltransfile.[overtime],
    prlpayroltransfile.[lateness_absent],
    prlemployeemaster.fname,
    prlemployeemaster.mname,
    prlemployeemaster.lname;
END     
Select pfno ,names ,basicpay ,Overtime ,A_10 ,A_17 ,A_20 ,D_1 ,D_2 ,D_3 , Manhrlost ,Loans ,(basicpay+Overtime+isnull(A_10,0)+isnull(A_17,0)+isnull(A_20,0)-isnull(D_1,0)-isnull(D_2,0)-isnull(D_3,0) -Manhrlost -Loans) as netpay from @masterroll order by pfno asc
GO
/****** Object:  StoredProcedure [dbo].[getmatrix]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[getmatrix]
    AS
    BEGIN


    DECLARE @masterroll Table (A_10 float,A_17 float,A_20 float,D_1 float,D_2 float,D_3 float, pfno char(20),names varchar(50),basicpay float,Loans float
     );   insert into @masterroll  select  pfle_10.amount  ,  pfle_17.amount  ,  pfle_20.amount  ,  pfle_1.amount  ,  pfle_2.amount  ,  pfle_3.amount  ,  prlemployeemaster.pf_no as  pfno ,
    Rtrim(prlemployeemaster.fname) +' '+ Rtrim(prlemployeemaster.mname)+' '+ Rtrim(prlemployeemaster.lname),
    prlemployeemaster.basicpay ,
   (select 
   isnull(sum([prlloantrans].[amount]),0)+ isnull(sum([prlloantrans].[interest]),0) 
   from prlloantrans where prlloantrans.[pfno]=prlemployeemaster.pf_no 
   and prlloantrans.[payroll_id] ='19') as totalloan
   from prlemployeemaster
   left join [prlmatrix] on [prlmatrix].[pfno]=prlemployeemaster.pf_no  left join [prlmatrix]  pfle_10  on [prlmatrix].[pfno]=pfle_10.[pfno] and pfle_10.prodid='10' left join [prlmatrix]  pfle_17  on [prlmatrix].[pfno]=pfle_17.[pfno] and pfle_17.prodid='17' left join [prlmatrix]  pfle_20  on [prlmatrix].[pfno]=pfle_20.[pfno] and pfle_20.prodid='20' left join [prlmatrix]  pfle_1  on [prlmatrix].[pfno]=pfle_1.[pfno] and pfle_1.prodid='1' left join [prlmatrix]  pfle_2  on [prlmatrix].[pfno]=pfle_2.[pfno] and pfle_2.prodid='2' left join [prlmatrix]  pfle_3  on [prlmatrix].[pfno]=pfle_3.[pfno] and pfle_3.prodid='3' where ((prlemployeemaster.[dateterminated] < prlemployeemaster.[dateemployed]) 
     or (prlemployeemaster.[dateterminated] is null))  Group by   pfle_10.amount,  pfle_17.amount,  pfle_20.amount,  pfle_1.amount,  pfle_2.amount,  pfle_3.amount, prlemployeemaster.pf_no ,
    prlemployeemaster.basicpay ,
    prlemployeemaster.fname,
    prlemployeemaster.mname,
    prlemployeemaster.lname;
END     
Select pfno ,names ,basicpay ,A_10 ,A_17 ,A_20 ,D_1 ,D_2 ,D_3 ,  Loans ,(basicpay +isnull(A_10,0)+isnull(A_17,0)+isnull(A_20,0)-isnull(D_1,0)-isnull(D_2,0)-isnull(D_3,0)- isnull(Loans,0)  ) as netpay from @masterroll order by pfno asc
GO
/****** Object:  StoredProcedure [dbo].[MakeTimedataReady]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[MakeTimedataReady] 
    AS BEGIN 
    
    Declare @today smalldatetime, @days int 
     select @today = cast('2018-04-13' as smalldatetime)
          
    Declare @DateName varchar(max),@CurrentDate smalldatetime, 
    @offday int, @offmonth int ,@LastWorkingDay smalldatetime
    
    select @LastWorkingDay=cast('2018-04-13' as smalldatetime)

    Declare @workingdays table (namevar varchar(max),date datetime);

            with View_MONTH as
            (
               select cast('2018-04-13' as smalldatetime) as DateValue
               union all
               select DATEADD (D,1,DateValue) from View_MONTH where DATEADD (D,1,DateValue) <= @LastWorkingDay
            )

            insert into @workingdays select DATENAME(WEEKDAY,DateValue),DateValue from View_MONTH

         
            DECLARE mymonth CURSOR FOR SELECT namevar ,date  from @workingdays
            OPEN mymonth
            FETCH NEXT FROM mymonth INTO @DateName ,@CurrentDate
            WHILE @@FETCH_STATUS = 0
            BEGIN
            
                Insert into prltimesheet (pfno,date,period,recommended,ShouldLogIn)
               (select [pf_no],@CurrentDate,'21',[noofhrsperday] ,1 FROM [prlemployeemaster] where prlemployeemaster.[freqcode]=0 )
   
                   Declare @isworkingday bit,@holiday varchar(50)
                   
                    SELECT @isworkingday=[isworkingday] 
                    FROM [Dayoftheweeks] where [Dayoftheweek]=@DateName
                   
                    if(@isworkingday=1) 
                    begin
                         
                        SELECT @holiday = [name] from [prlspecialdays] 
                        where day=datepart(d,@CurrentDate) and month=datepart(m,@CurrentDate)
                        
                         if(@holiday is null)  
                         begin 
                              Insert into prltimesheet (pfno,date,period,recommended,ShouldLogIn)
                              (select [pf_no],@CurrentDate,'21',[noofhrsperday] ,1
                               FROM [prlemployeemaster] where [pf_no] not in (select [pfno] from [prlstaffleaveplanner] where @CurrentDate between [leavedue] and [leavend]) 
                               and prlemployeemaster.[freqcode]>0 )
                         end
                                                  
                    end
   
                FETCH NEXT FROM mymonth INTO @DateName ,@CurrentDate
            END
            
    CLOSE mymonth
    DEALLOCATE mymonth
 
END
GO
/****** Object:  StoredProcedure [dbo].[onepayslip]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[onepayslip]
AS
BEGIN

Declare @pfno char(20),@names varchar(50),@basicpay float,@overtime float,@lates float,@auto int,@balance float
Declare @allowances float,@deductions float,@monthof varchar(20),@yearof varchar(20) ,@taxableincome float
Declare @idno varchar(50),@pin_no varchar(50),@bankcode  varchar(50),@bankac  varchar(50),@designation  varchar(50),@locationname varchar(50)
Declare @insurancerelief float ,@mortgagerelief float

set @auto = 0;
set @balance = 0;



 -- Insert statements for procedure here
select @monthof = DATENAME(month,todate) from prlmrollperiods where pkey=19
select @yearof = datepart(yy,todate) from prlmrollperiods where pkey=19

DECLARE @payslips Table
(
    ItemId int ,
    Pfno  char(20) ,
    Notes  varchar(100),
    Amount float, 
    LoanBalance float,
    Format char(1)
)


  DECLARE trans CURSOR FOR SELECT  pt.pfno
      ,'Names :'+Rtrim(pm.[status])+' '+Rtrim(pm.[fname])+' '+Rtrim(pm.[mname])+' '+Rtrim( pm.[lname])
      ,pt.basicpay , pt.overtime , pt.lateness_absent,(isnull(pt.basicpay,0) + isnull(pt.overtime,0) - isnull(pt.lateness_absent,0)+ isnull(pt.allowances,0) - isnull(pt.pension,0) + isnull(pt.[non_cash_benefits],0))
       ,pm.[idno] , pm.[pin_no], bk.bankbranch , pm.bankacno
       ,prlpositions.name as Designation, lc.[name] as branch
       ,pm.insurancerelief , pm.mortagerelief
    FROM [prlemployeemaster] pm
	left join  [employeebnkbranch]  bk on pm.bankcode=bk.code
	LEFT JOIN  prlpositions  on pm.position=prlpositions.code
        left join  prlestablishment lc on  pm.[branch]=lc.code
        left join  prlpayroltransfile pt on  pm.pf_no=pt.pfno 
	join prlmrollperiods  on pt.payroll_id = prlmrollperiods.pkey 
    where   pt.pfno='PF003'  and prlmrollperiods.pkey ='19' 
        order by pt.pfno

OPEN trans
FETCH NEXT FROM trans INTO @pfno ,@names ,@basicpay ,@overtime ,@lates , @taxableincome ,@idno ,
@pin_no ,@bankcode ,@bankac ,@designation ,@locationname,@insurancerelief,@mortgagerelief
WHILE @@FETCH_STATUS = 0
BEGIN


set @balance = @basicpay

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Pay Slip For '+@monthof+' of '+@yearof,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'PR No:'+convert(varchar(10),@pfno),@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'ID Number : '+@idno+' |PIN :'+@pin_no,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Bank Code: '+@bankcode+' |Bank Account :'+@bankac ,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Branch: '+@locationname+' |Position :'+@designation,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,@names,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'l')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,Amount) values (@auto,'Basic Pay',@pfno,'N',@basicpay)

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Add Allowances:',@pfno,'N')


if(@overtime is not null or @overtime>0)
begin
set @auto = @auto+1;
	insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Over Time',@pfno,'N',@overtime)
	set @balance = @balance+@overtime
end

insert into @payslips (ItemId,Notes,Pfno,Format,amount) (select @auto , description, @pfno, 'N', amount  
from prlpaydetailstransfile where pfno=@pfno and payroll_id=19 and deduction=1 and amount>0 )

set @auto = @auto + 1;
select @allowances = sum(amount) from prlpaydetailstransfile where pfno=@pfno and payroll_id=19 and deduction=1

set @auto = @auto + 1;
set @balance = @balance+ isnull(@allowances,0)
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Total Allowances :',@pfno,'N',@allowances+isnull(@overtime,0))

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Gross Pay :',@pfno,'N',@balance)

---- this is where total allowances end

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'l')


set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Less Deductions :',@pfno,'N')


set @deductions=0


if(@lates is not null or @lates>0)
begin
        set @auto = @auto+1;
	insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Man Hours lost:',@pfno,'N',@lates)
	set @balance = @balance - @lates
        set @deductions = @deductions + @lates
end


set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) (select @auto , description, @pfno, 'N', amount 
from prlpaydetailstransfile where pfno=@pfno and payroll_id=19 and deduction=0 and amount>0
    and ([non_cash_benefits]=1 or [non_cash_benefits]=2 or [non_cash_benefits] is null))


select @allowances = sum(amount) from prlpaydetailstransfile where pfno=@pfno and payroll_id=19  and deduction=0 
set @balance = @balance - isnull(@allowances,0)
set @deductions = @deductions + isnull(@allowances,0)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Less Loans / Balances',@pfno,'N')



set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount,LoanBalance) 
(select @auto, Prlproducts.description, @pfno, 'N', 
prlloantrans.amount ,
case when prlstaffloans.[saving]=1 then
    prlstaffloans.principal+sum(prlloantrans.amount) 
else
    prlstaffloans.principal-sum(prlloantrans.amount)
end
from prlloantrans  
join prlstaffloans on prlloantrans.loanindex = prlstaffloans.loanindex 
join Prlproducts on prlstaffloans.deductcode=Prlproducts.code 
where prlloantrans.pfno = @pfno and prlloantrans.payroll_id=19  and prlloantrans.amount>0  
    group by Prlproducts.description,prlstaffloans.principal,prlloantrans.amount,prlstaffloans.saving)

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Less Loan Interest :',@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) 
(select @auto, '% on '+Prlproducts.description, @pfno, 'N', prlloantrans.interest  
from prlloantrans  join prlstaffloans on prlloantrans.loanindex = prlstaffloans.loanindex 
join Prlproducts on prlstaffloans.deductcode = Prlproducts.code 
where prlloantrans.pfno = @pfno and prlloantrans.payroll_id=19 and prlloantrans.interest>0)



select @allowances = sum(isnull(prlloantrans.interest,0)+prlloantrans.amount)  from prlloantrans where prlloantrans.pfno = @pfno and prlloantrans.payroll_id =19
set @deductions = @deductions + isnull(@allowances,0)
set @balance = @balance - isnull(@allowances,0)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')


set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Total Deductions :',@pfno,'N',@deductions)


set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Net pay :',@pfno,'N',@balance)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'X')


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format,Notes) values (@auto,@pfno,'N','PAYSLIP SUMMARY')

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format)
(select @auto,'List of NON-CASH Benefits applied:',@pfno,'N')

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) 
(select @auto , lower(description), @pfno, 'N', amount  
from prlpaydetailstransfile where pfno=@pfno and payroll_id=19  and [non_cash_benefits]=3)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount)(select @auto,'Gross Taxable Pay',@pfno,'N',@taxableincome)


set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) (select @auto,lower(prlreliefs.[name]),@pfno,'N',reliefdeducted
from prlpaydetailstransfile join prlreliefs on prlpaydetailstransfile.code=prlreliefs.productcode
where pfno=@pfno and payroll_id=19 and deduction=0 and reliefdeducted>0)

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount)(select @auto,'Insurance Relief',@pfno,'N',[insurancerelief]
from [prlpayroltransfile] where pfno=@pfno and payroll_id=19)

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'K')



set @balance=0;
set @deductions=0;
set @allowances=0;


FETCH NEXT FROM trans INTO @pfno ,@names ,@basicpay ,@overtime ,@lates ,@taxableincome,@idno , @pin_no ,@bankcode 
,@bankac ,@designation ,@locationname,@insurancerelief,@mortgagerelief
end



CLOSE trans
DEALLOCATE trans

select * from @payslips  order by ItemId

END

GO
/****** Object:  StoredProcedure [dbo].[payslips]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[payslips]
AS
BEGIN

Declare @pfno char(20),@names varchar(50),@basicpay float,@overtime float,@lates float,@auto int,@balance float
Declare @allowances float,@deductions float,@monthof varchar(20),@yearof varchar(20) ,@taxableincome float
Declare @idno varchar(50),@pin_no varchar(50),@bankcode  varchar(50),@bankac  varchar(50),@designation  varchar(50),@locationname varchar(50)
Declare @insurancerelief float ,@mortgagerelief float

set @auto = 0;
set @balance = 0;



 -- Insert statements for procedure here
select @monthof = DATENAME(month,todate) from prlmrollperiods where pkey=19
select @yearof = datepart(yy,todate) from prlmrollperiods where pkey=19

DECLARE @payslips Table
(
    ItemId int ,
    Pfno  char(20) ,
    Notes  varchar(100),
    Amount float, 
    LoanBalance float,
    Format char(1)
)


  DECLARE trans CURSOR FOR SELECT  pt.pfno
      ,'Names :'+Rtrim(pm.[status])+' '+Rtrim(pm.[fname])+' '+Rtrim(pm.[mname])+' '+Rtrim( pm.[lname])
      ,pt.basicpay , pt.overtime , pt.lateness_absent,(isnull(pt.basicpay,0) + isnull(pt.overtime,0) - isnull(pt.lateness_absent,0)+ isnull(pt.allowances,0) - isnull(pt.pension,0) + isnull(pt.[non_cash_benefits],0))
       ,pm.[idno] , pm.[pin_no], bk.bankbranch , pm.bankacno
       ,prlpositions.name as Designation, lc.[name] as branch
       ,pm.insurancerelief , pm.mortagerelief
    FROM [prlemployeemaster] pm
	left join  [employeebnkbranch]  bk on pm.bankcode=bk.code
	LEFT JOIN  prlpositions  on pm.position=prlpositions.code
        left join  prlestablishment lc on  pm.[branch]=lc.code
        left join  prlpayroltransfile pt on  pm.pf_no=pt.pfno 
	join prlmrollperiods  on pt.payroll_id = prlmrollperiods.pkey 
    where   pt.pfno='PF0022              '  and prlmrollperiods.pkey ='19' 
        order by pt.pfno

OPEN trans
FETCH NEXT FROM trans INTO @pfno ,@names ,@basicpay ,@overtime ,@lates , @taxableincome ,@idno ,
@pin_no ,@bankcode ,@bankac ,@designation ,@locationname,@insurancerelief,@mortgagerelief
WHILE @@FETCH_STATUS = 0
BEGIN


set @balance = @basicpay

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Pay Slip For '+@monthof+' of '+@yearof,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'PR No:'+convert(varchar(10),@pfno),@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'ID Number : '+@idno+' |PIN :'+@pin_no,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Bank Code: '+@bankcode+' |Bank Account :'+@bankac ,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Branch: '+@locationname+' |Position :'+@designation,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,@names,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'l')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,Amount) values (@auto,'Basic Pay',@pfno,'N',@basicpay)

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Add Allowances:',@pfno,'N')


if(@overtime is not null or @overtime>0)
begin
set @auto = @auto+1;
	insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Over Time',@pfno,'N',@overtime)
	set @balance = @balance+@overtime
end

insert into @payslips (ItemId,Notes,Pfno,Format,amount) (select @auto , description, @pfno, 'N', amount  
from prlpaydetailstransfile where pfno=@pfno and payroll_id=19 and deduction=1 and amount>0 )

set @auto = @auto + 1;
select @allowances = sum(amount) from prlpaydetailstransfile where pfno=@pfno and payroll_id=19 and deduction=1

set @auto = @auto + 1;
set @balance = @balance+ isnull(@allowances,0)
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Total Allowances :',@pfno,'N',@allowances+isnull(@overtime,0))

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Gross Pay :',@pfno,'N',@balance)

---- this is where total allowances end

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'l')


set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Less Deductions :',@pfno,'N')


set @deductions=0


if(@lates is not null or @lates>0)
begin
        set @auto = @auto+1;
	insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Man Hours lost:',@pfno,'N',@lates)
	set @balance = @balance - @lates
        set @deductions = @deductions + @lates
end


set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) (select @auto , description, @pfno, 'N', amount 
from prlpaydetailstransfile where pfno=@pfno and payroll_id=19 and deduction=0 and amount>0
    and ([non_cash_benefits]=1 or [non_cash_benefits]=2 or [non_cash_benefits] is null))


select @allowances = sum(amount) from prlpaydetailstransfile where pfno=@pfno and payroll_id=19  and deduction=0 
set @balance = @balance - isnull(@allowances,0)
set @deductions = @deductions + isnull(@allowances,0)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Less Loans / Balances',@pfno,'N')



set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount,LoanBalance) 
(select @auto, Prlproducts.description, @pfno, 'N', 
prlloantrans.amount ,
case when prlstaffloans.[saving]=1 then
    prlstaffloans.principal+sum(prlloantrans.amount) 
else
    prlstaffloans.principal-sum(prlloantrans.amount)
end
from prlloantrans  
join prlstaffloans on prlloantrans.loanindex = prlstaffloans.loanindex 
join Prlproducts on prlstaffloans.deductcode=Prlproducts.code 
where prlloantrans.pfno = @pfno and prlloantrans.payroll_id=19  and prlloantrans.amount>0  
    group by Prlproducts.description,prlstaffloans.principal,prlloantrans.amount,prlstaffloans.saving)

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Less Loan Interest :',@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) 
(select @auto, '% on '+Prlproducts.description, @pfno, 'N', prlloantrans.interest  
from prlloantrans  join prlstaffloans on prlloantrans.loanindex = prlstaffloans.loanindex 
join Prlproducts on prlstaffloans.deductcode = Prlproducts.code 
where prlloantrans.pfno = @pfno and prlloantrans.payroll_id=19 and prlloantrans.interest>0)



select @allowances = sum(isnull(prlloantrans.interest,0)+prlloantrans.amount)  from prlloantrans where prlloantrans.pfno = @pfno and prlloantrans.payroll_id =19
set @deductions = @deductions + isnull(@allowances,0)
set @balance = @balance - isnull(@allowances,0)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')


set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Total Deductions :',@pfno,'N',@deductions)


set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Net pay :',@pfno,'N',@balance)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'X')


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format,Notes) values (@auto,@pfno,'N','PAYSLIP SUMMARY')

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format)
(select @auto,'List of NON-CASH Benefits applied:',@pfno,'N')

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) 
(select @auto , lower(description), @pfno, 'N', amount  
from prlpaydetailstransfile where pfno=@pfno and payroll_id=19  and [non_cash_benefits]=3)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount)(select @auto,'Gross Taxable Pay',@pfno,'N',@taxableincome)


set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) (select @auto,lower(prlreliefs.[name]),@pfno,'N',reliefdeducted
from prlpaydetailstransfile join prlreliefs on prlpaydetailstransfile.code=prlreliefs.productcode
where pfno=@pfno and payroll_id=19 and deduction=0 and reliefdeducted>0)

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount)(select @auto,'Insurance Relief',@pfno,'N',[insurancerelief]
from [prlpayroltransfile] where pfno=@pfno and payroll_id=19)

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'K')



set @balance=0;
set @deductions=0;
set @allowances=0;


FETCH NEXT FROM trans INTO @pfno ,@names ,@basicpay ,@overtime ,@lates ,@taxableincome,@idno , @pin_no ,@bankcode 
,@bankac ,@designation ,@locationname,@insurancerelief,@mortgagerelief
end



CLOSE trans
DEALLOCATE trans

select * from @payslips  order by ItemId

END

GO
/****** Object:  StoredProcedure [dbo].[payslips_Email]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[payslips_Email]
AS
BEGIN

Declare @pfno char(20),@names varchar(50),@basicpay float,@overtime float,@lates float,@auto int,@balance float
Declare @allowances float,@deductions float,@monthof varchar(20),@yearof varchar(20) ,@taxableincome float
Declare @idno varchar(50),@pin_no varchar(50),@bankcode  varchar(50),@bankac  varchar(50),@designation  varchar(50),@locationname varchar(50)
Declare @insurancerelief float ,@mortgagerelief float

set @auto = 0;
set @balance = 0;



 -- Insert statements for procedure here
select @monthof = DATENAME(month,todate) from prlmrollperiods where pkey=19
select @yearof = datepart(yy,todate) from prlmrollperiods where pkey=19

DECLARE @payslips Table
(
    ItemId int ,
    Pfno  char(20) ,
    Notes  varchar(100),
    Amount float, 
    LoanBalance float,
    Format char(1)
)


  DECLARE trans CURSOR FOR SELECT    
       pt.pfno
       ,'Names :'+Rtrim(pm.[status])+' '+Rtrim(pm.[fname])+' '+Rtrim(pm.[mname])+' '+Rtrim( pm.[lname])
       ,pt.basicpay , pt.overtime , pt.lateness_absent,(isnull(pt.basicpay,0) + isnull(pt.overtime,0) - isnull(pt.lateness_absent,0)+ isnull(pt.allowances,0) - isnull(pt.pension,0) + isnull(pt.[non_cash_benefits],0))
       ,pm.[idno] , pm.[pin_no], bk.bankbranch , pm.bankacno
       ,prlpositions.name as Designation, lc.[name] as branch
       ,pm.insurancerelief , pm.mortagerelief
    FROM [prlemployeemaster] pm
	left join  [employeebnkbranch]  bk on pm.bankcode=bk.code
	LEFT JOIN  prlpositions  on pm.position=prlpositions.code
        left join  prlestablishment lc on  pm.[branch]=lc.code
        left join  prlpayroltransfile pt on  pm.pf_no=pt.pfno 
	join prlmrollperiods  on pt.payroll_id = prlmrollperiods.pkey 
    where   pt.pfno='PF23'  and prlmrollperiods.pkey ='19'  
        order by pt.pfno

OPEN trans
FETCH NEXT FROM trans INTO @pfno ,@names ,@basicpay ,@overtime ,@lates , @taxableincome ,@idno ,
@pin_no ,@bankcode ,@bankac ,@designation ,@locationname,@insurancerelief,@mortgagerelief
WHILE @@FETCH_STATUS = 0
BEGIN


set @balance = @basicpay

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Pay Slip For '+@monthof+' of '+@yearof,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'PR No:'+convert(varchar(10),@pfno),@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'ID Number : '+@idno+' |PIN :'+@pin_no,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Bank Code: '+@bankcode+' |Bank Account :'+@bankac ,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Branch: '+@locationname+' |Position :'+@designation,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,@names,@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'l')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,Amount) values (@auto,'Basic Pay',@pfno,'N',@basicpay)

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Add Allowances:',@pfno,'N')


if(@overtime is not null or @overtime>0)
begin
set @auto = @auto+1;
	insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Over Time',@pfno,'N',@overtime)
	set @balance = @balance+@overtime
end

insert into @payslips (ItemId,Notes,Pfno,Format,amount) (select @auto , description, @pfno, 'N', amount  
from prlpaydetailstransfile where pfno=@pfno and payroll_id=19 and deduction=1 and amount>0 )

set @auto = @auto + 1;
select @allowances = sum(amount) from prlpaydetailstransfile where pfno=@pfno and payroll_id=19 and deduction=1

set @auto = @auto + 1;
set @balance = @balance+ isnull(@allowances,0)
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Total Allowances :',@pfno,'N',@allowances+isnull(@overtime,0))

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Gross Pay :',@pfno,'N',@balance)

---- this is where total allowances end

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'l')


set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Less Deductions :',@pfno,'N')


set @deductions=0


if(@lates is not null or @lates>0)
begin
        set @auto = @auto+1;
	insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Man Hours lost:',@pfno,'N',@lates)
	set @balance = @balance - @lates
        set @deductions = @deductions + @lates
end


set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) (select @auto , description, @pfno, 'N', amount 
from prlpaydetailstransfile where pfno=@pfno and payroll_id=19 and deduction=0 and amount>0
    and ([non_cash_benefits]=1 or [non_cash_benefits]=2 or [non_cash_benefits] is null))


select @allowances = sum(amount) from prlpaydetailstransfile where pfno=@pfno and payroll_id=19  and deduction=0 
set @balance = @balance - isnull(@allowances,0)
set @deductions = @deductions + isnull(@allowances,0)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Less Loans / Balances',@pfno,'N')



set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount,LoanBalance) 
(select @auto, Prlproducts.description, @pfno, 'N', 
prlloantrans.amount ,
case when prlstaffloans.[saving]=1 then
    prlstaffloans.principal+sum(prlloantrans.amount) 
else
    prlstaffloans.principal-sum(prlloantrans.amount)
end
from prlloantrans  
join prlstaffloans on prlloantrans.loanindex = prlstaffloans.loanindex 
join Prlproducts on prlstaffloans.deductcode=Prlproducts.code 
where prlloantrans.pfno = @pfno and prlloantrans.payroll_id=19  and prlloantrans.amount>0  
    group by Prlproducts.description,prlstaffloans.principal,prlloantrans.amount,prlstaffloans.saving)

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format) values (@auto,'Less Loan Interest :',@pfno,'N')

set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) 
(select @auto, '% on '+Prlproducts.description, @pfno, 'N', prlloantrans.interest  
from prlloantrans  join prlstaffloans on prlloantrans.loanindex = prlstaffloans.loanindex 
join Prlproducts on prlstaffloans.deductcode = Prlproducts.code 
where prlloantrans.pfno = @pfno and prlloantrans.payroll_id=19 and prlloantrans.interest>0)



select @allowances = sum(isnull(prlloantrans.interest,0)+prlloantrans.amount)  from prlloantrans where prlloantrans.pfno = @pfno and prlloantrans.payroll_id =19
set @deductions = @deductions + isnull(@allowances,0)
set @balance = @balance - isnull(@allowances,0)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')


set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Total Deductions :',@pfno,'N',@deductions)


set @auto = @auto+1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) values (@auto,'Net pay :',@pfno,'N',@balance)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'X')


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format,Notes) values (@auto,@pfno,'N','PAYSLIP SUMMARY')

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format)
(select @auto,'List of NON-CASH Benefits applied:',@pfno,'N')

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) 
(select @auto , lower(description), @pfno, 'N', amount  
from prlpaydetailstransfile where pfno=@pfno and payroll_id=19  and [non_cash_benefits]=3)


set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount)(select @auto,'Gross Taxable Pay',@pfno,'N',@taxableincome)


set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount) (select @auto,lower(prlreliefs.[name]),@pfno,'N',reliefdeducted
from prlpaydetailstransfile join prlreliefs on prlpaydetailstransfile.code=prlreliefs.productcode
where pfno=@pfno and payroll_id=19 and deduction=0 and reliefdeducted>0)

set @auto = @auto + 1;
insert into @payslips (ItemId,Notes,Pfno,Format,amount)(select @auto,'Insurance Relief',@pfno,'N',[insurancerelief]
from [prlpayroltransfile] where pfno=@pfno and payroll_id=19)

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'b')

set @auto = @auto+1;
insert into @payslips (ItemId,Pfno,Format) values (@auto,@pfno,'K')



set @balance=0;
set @deductions=0;
set @allowances=0;


FETCH NEXT FROM trans INTO @pfno ,@names ,@basicpay ,@overtime ,@lates ,@taxableincome,@idno , @pin_no ,@bankcode 
,@bankac ,@designation ,@locationname,@insurancerelief,@mortgagerelief
end



CLOSE trans
DEALLOCATE trans

select * from @payslips  order by ItemId

END

GO
/****** Object:  StoredProcedure [dbo].[printbymonth]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[printbymonth]
    AS
    BEGIN

DECLARE @masterroll Table (pfno char(20),
nssfno varchar(20),
nhifno varchar(20),
payeno varchar(20),
names varchar(50),
amount float,
empl float);

insert into @masterroll select
    prlpaydetailstransfile.pfno,
    prlemployeemaster.nssf_no,
    prlemployeemaster.nhif_no,
    prlemployeemaster.pin_no,
    Rtrim(prlemployeemaster.fname) +' '+ Rtrim(prlemployeemaster.mname)+' '+ Rtrim(prlemployeemaster.lname),
    sum(prlpaydetailstransfile.amount) as total,
    isnull(sum(prlpaydetailstransfile.employercontribution),0) as empl
    from  prlpaydetailstransfile join prlemployeemaster
    on prlpaydetailstransfile.[pfno] = prlemployeemaster.pf_no
    where code='3' and [payroll_id] in
    (select pkey from prlmrollperiods
    where fromdate >='2018-01-03' and todate <='2018-03-31')
    Group by  prlpaydetailstransfile.pfno ,
    prlemployeemaster.nssf_no,
    prlemployeemaster.nhif_no,
    prlemployeemaster.pin_no,
    prlemployeemaster.fname,
    prlemployeemaster.mname,
    prlemployeemaster.lname
END

select pfno ,nssfno ,nhifno ,payeno ,names ,amount ,empl from @masterroll ;
GO
/****** Object:  StoredProcedure [dbo].[printleaveappliction]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[printleaveappliction] AS BEGIN

DECLARE @leaveapplication Table (refno char(10),pfno char(20),handover char(20),year int,
leavedue datetime,leavend datetime,days int,typeofleave char(2),status int,
email varchar(50),appliedby varchar(50),sub varchar(50),appliedby2 varchar(50),sub1 varchar(50));

insert into @leaveapplication
    SELECT prlstaffleaveplanner.[refno]
      ,prlstaffleaveplanner.[pfno]
      ,prlstaffleaveplanner.[handover]
      ,prlstaffleaveplanner.[year]
      ,prlstaffleaveplanner.[leavedue]
      ,prlstaffleaveplanner.[leavend]
      ,prlstaffleaveplanner.[days]
      ,prlstaffleaveplanner.[typeofleave]
      ,prlstaffleaveplanner.[status]
      ,prlemployeemaster.[email]
      ,prlemployeemaster.[fname] +' '+prlemployeemaster.[mname]+' '+prlemployeemaster.[lname]
      ,prlemployeemaster2.[fname] +' '+prlemployeemaster2.[mname]+' '+prlemployeemaster2.[lname]
      ,prlpositions.[name]
      ,prlpositions2.[name]
      from prlstaffleaveplanner
      left join prlemployeemaster on prlstaffleaveplanner.pfno=prlemployeemaster.pf_no
      left join prlemployeemaster prlemployeemaster2 on prlstaffleaveplanner.handover=prlemployeemaster2.pf_no
      left join prlpositions on prlemployeemaster.position=prlpositions.code
      left join prlpositions prlpositions2 on prlemployeemaster2.position=prlpositions2.code
   where prlstaffleaveplanner.[refno]='0091'

   select * from @leaveapplication

END

GO
/****** Object:  StoredProcedure [dbo].[printloans]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[printloans] AS BEGIN

DECLARE @masterroll Table (pfno char(20),names varchar(50),amount float,interest float,gross float);

insert into @masterroll 
   select  prlemployeemaster.idno,
   Rtrim(prlemployeemaster.fname) +' '+ Rtrim(prlemployeemaster.mname)+' '+ Rtrim(prlemployeemaster.lname) as names,
   prlloantrans.amount,
   prlloantrans.interest ,
   isnull(prlloantrans.amount,0) + isnull(prlloantrans.interest,0) as gross
   from prlloantrans 
   join prlemployeemaster on prlemployeemaster.pf_no=prlloantrans.pfno
   full join prlstaffloans on prlloantrans.loanindex = prlstaffloans.loanindex 
   join Prlproducts on prlstaffloans.deductcode=Prlproducts.code 
   where prlloantrans.payroll_id=''  
   and Prlproducts.code='10' 
       and (prlstaffloans.[saving]=0 or prlstaffloans.[saving] is null) 
       and  prlloantrans.amount > 0
    Group by prlemployeemaster.idno, 
    prlemployeemaster.fname,
    prlemployeemaster.mname, 
    prlemployeemaster.lname, 
    prlloantrans.amount,
    prlloantrans.interest 
 
    
END

select * from @masterroll ;

GO
/****** Object:  StoredProcedure [dbo].[printmasteroll]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[printmasteroll]
    AS
    BEGIN

    DECLARE @masterroll Table (A_6 numeric(10,2),A_7 numeric(10,2),A_8 numeric(10,2),A_9 numeric(10,2),A_10 numeric(10,2),A_11 numeric(10,2),A_12 numeric(10,2),A_13 numeric(10,2),A_14 numeric(10,2),A_15 numeric(10,2),A_16 numeric(10,2),A_17 numeric(10,2),A_18 numeric(10,2),A_19 numeric(10,2),A_20 numeric(10,2),A_999999 numeric(10,2),D_1000000 numeric(10,2),D_1 numeric(10,2),D_2 numeric(10,2),D_3 numeric(10,2), pfno char(20),names varchar(50),basicpay float,
        Overtime float,Manhrlost float,Loans float
     );   insert into @masterroll select  pfle_6.amount,  pfle_7.amount,  pfle_8.amount,  pfle_9.amount,  pfle_10.amount,  pfle_11.amount,  pfle_12.amount,  pfle_13.amount,  pfle_14.amount,  pfle_15.amount,  pfle_16.amount,  pfle_17.amount,  pfle_18.amount,  pfle_19.amount,  pfle_20.amount,  pfle_999999.amount,  pfle_1000000.amount,  pfle_1.amount,  pfle_2.amount,  pfle_3.amount,  prlpayroltransfile.pfno ,
    Rtrim(prlemployeemaster.fname) +' '+ Rtrim(prlemployeemaster.mname)+' '+ Rtrim(prlemployeemaster.lname),
    prlpayroltransfile.basicpay ,
    prlpayroltransfile.[overtime],
    prlpayroltransfile.[lateness_absent] ,
    sum(isnull([prlloantrans].[amount],0) + isnull([prlloantrans].[interest],0)) as totalloan
    from  prlpayroltransfile join prlemployeemaster on prlpayroltransfile.[pfno]=prlemployeemaster.pf_no
    left join prlloantrans on  prlpayroltransfile.[payroll_id]=prlloantrans.[payroll_id]
    and prlpayroltransfile.[pfno]=prlloantrans.[pfno]  left outer join prlpaydetailstransfile  pfle_6 on
          prlpayroltransfile.[payroll_id]=pfle_6.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_6.[pfno]
          and pfle_6.code='6' left outer join prlpaydetailstransfile  pfle_7 on
          prlpayroltransfile.[payroll_id]=pfle_7.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_7.[pfno]
          and pfle_7.code='7' left outer join prlpaydetailstransfile  pfle_8 on
          prlpayroltransfile.[payroll_id]=pfle_8.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_8.[pfno]
          and pfle_8.code='8' left outer join prlpaydetailstransfile  pfle_9 on
          prlpayroltransfile.[payroll_id]=pfle_9.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_9.[pfno]
          and pfle_9.code='9' left outer join prlpaydetailstransfile  pfle_10 on
          prlpayroltransfile.[payroll_id]=pfle_10.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_10.[pfno]
          and pfle_10.code='10' left outer join prlpaydetailstransfile  pfle_11 on
          prlpayroltransfile.[payroll_id]=pfle_11.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_11.[pfno]
          and pfle_11.code='11' left outer join prlpaydetailstransfile  pfle_12 on
          prlpayroltransfile.[payroll_id]=pfle_12.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_12.[pfno]
          and pfle_12.code='12' left outer join prlpaydetailstransfile  pfle_13 on
          prlpayroltransfile.[payroll_id]=pfle_13.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_13.[pfno]
          and pfle_13.code='13' left outer join prlpaydetailstransfile  pfle_14 on
          prlpayroltransfile.[payroll_id]=pfle_14.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_14.[pfno]
          and pfle_14.code='14' left outer join prlpaydetailstransfile  pfle_15 on
          prlpayroltransfile.[payroll_id]=pfle_15.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_15.[pfno]
          and pfle_15.code='15' left outer join prlpaydetailstransfile  pfle_16 on
          prlpayroltransfile.[payroll_id]=pfle_16.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_16.[pfno]
          and pfle_16.code='16' left outer join prlpaydetailstransfile  pfle_17 on
          prlpayroltransfile.[payroll_id]=pfle_17.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_17.[pfno]
          and pfle_17.code='17' left outer join prlpaydetailstransfile  pfle_18 on
          prlpayroltransfile.[payroll_id]=pfle_18.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_18.[pfno]
          and pfle_18.code='18' left outer join prlpaydetailstransfile  pfle_19 on
          prlpayroltransfile.[payroll_id]=pfle_19.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_19.[pfno]
          and pfle_19.code='19' left outer join prlpaydetailstransfile  pfle_20 on
          prlpayroltransfile.[payroll_id]=pfle_20.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_20.[pfno]
          and pfle_20.code='20' left outer join prlpaydetailstransfile  pfle_999999 on
          prlpayroltransfile.[payroll_id]=pfle_999999.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_999999.[pfno]
          and pfle_999999.code='999999' left outer join prlpaydetailstransfile  pfle_1000000 on
          prlpayroltransfile.[payroll_id]=pfle_1000000.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_1000000.[pfno]
          and pfle_1000000.code='1000000' left outer join prlpaydetailstransfile  pfle_1 on
          prlpayroltransfile.[payroll_id]=pfle_1.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_1.[pfno]
          and pfle_1.code='1' left outer join prlpaydetailstransfile  pfle_2 on
          prlpayroltransfile.[payroll_id]=pfle_2.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_2.[pfno]
          and pfle_2.code='2' left outer join prlpaydetailstransfile  pfle_3 on
          prlpayroltransfile.[payroll_id]=pfle_3.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_3.[pfno]
          and pfle_3.code='3' where  prlpayroltransfile.[payroll_id]='19' Group by   pfle_6.amount,  pfle_7.amount,  pfle_8.amount,  pfle_9.amount,  pfle_10.amount,  pfle_11.amount,  pfle_12.amount,  pfle_13.amount,  pfle_14.amount,  pfle_15.amount,  pfle_16.amount,  pfle_17.amount,  pfle_18.amount,  pfle_19.amount,  pfle_20.amount,  pfle_999999.amount,  pfle_1000000.amount,  pfle_1.amount,  pfle_2.amount,  pfle_3.amount, prlpayroltransfile.pfno ,
    prlpayroltransfile.basicpay ,
    prlpayroltransfile.[overtime],
    prlpayroltransfile.[lateness_absent],
    prlemployeemaster.fname,
    prlemployeemaster.mname,
    prlemployeemaster.lname;
END     Select pfno ,names ,basicpay ,Overtime ,A_6 ,A_7 ,A_8 ,A_9 ,A_10 ,A_11 ,A_12 ,A_13 ,A_14 ,A_15 ,A_16 ,A_17 ,A_18 ,A_19 ,A_20 ,A_999999 ,D_1 ,D_2 ,D_3 ,D_1000000 , Manhrlost ,Loans , (basicpay+ isnull(A_6,0)+ isnull(A_7,0)+ isnull(A_8,0)+ isnull(A_9,0)+ isnull(A_10,0)+ isnull(A_11,0)+ isnull(A_12,0)+ isnull(A_13,0)+ isnull(A_14,0)+ isnull(A_15,0)+ isnull(A_16,0)+ isnull(A_17,0)+ isnull(A_18,0)+ isnull(A_19,0)+ isnull(A_20,0)+ isnull(A_999999,0)+ isnull(Overtime,0) ) as allowances ,(Manhrlost+ isnull(D_1,0)+ isnull(D_2,0)+ isnull(D_3,0)+ isnull(D_1000000,0) + isnull(Loans,0)) as deductions from @masterroll 
GO
/****** Object:  StoredProcedure [dbo].[printnetpay]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[printnetpay]
    AS
    BEGIN

    DECLARE @masterroll Table (A_6 numeric(10,2),A_7 numeric(10,2),A_8 numeric(10,2),A_9 numeric(10,2),A_10 numeric(10,2),A_11 numeric(10,2),A_12 numeric(10,2),A_13 numeric(10,2),A_14 numeric(10,2),A_15 numeric(10,2),A_16 numeric(10,2),A_17 numeric(10,2),A_18 numeric(10,2),A_19 numeric(10,2),A_20 numeric(10,2),A_999999 numeric(10,2),D_1000000 numeric(10,2),D_1 numeric(10,2),D_2 numeric(10,2),D_3 numeric(10,2), pfno char(20),
        names varchar(50),
        basicpay float,
        Overtime float,
        Manhrlost float,
        Loans float,
        Bankaccount char(20),
        Bankcode varchar(20)
     );   insert into @masterroll select  pfle_6.amount,  pfle_7.amount,  pfle_8.amount,  pfle_9.amount,  pfle_10.amount,  pfle_11.amount,  pfle_12.amount,  pfle_13.amount,  pfle_14.amount,  pfle_15.amount,  pfle_16.amount,  pfle_17.amount,  pfle_18.amount,  pfle_19.amount,  pfle_20.amount,  pfle_999999.amount,  pfle_1000000.amount,  pfle_1.amount,  pfle_2.amount,  pfle_3.amount,  prlpayroltransfile.pfno ,
    Rtrim(prlemployeemaster.fname) +' '+ Rtrim(prlemployeemaster.mname)+' '+ Rtrim(prlemployeemaster.lname),
    prlpayroltransfile.basicpay ,
    prlpayroltransfile.[overtime],
    prlpayroltransfile.[lateness_absent] ,
    sum(isnull([prlloantrans].[amount],0)+ isnull([prlloantrans].[interest],0)) as totalloan,
    prlemployeemaster.[bankacno],
    prlemployeemaster.[bankcode2]
   
    from  prlpayroltransfile 
    join prlemployeemaster on prlpayroltransfile.[pfno]=prlemployeemaster.pf_no
    left join prlloantrans on prlpayroltransfile.[payroll_id]=prlloantrans.[payroll_id]
    and prlpayroltransfile.[pfno]=prlloantrans.[pfno]  left outer join prlpaydetailstransfile  pfle_6 on
          prlpayroltransfile.[payroll_id]=pfle_6.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_6.[pfno]
          and pfle_6.code='6' left outer join prlpaydetailstransfile  pfle_7 on
          prlpayroltransfile.[payroll_id]=pfle_7.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_7.[pfno]
          and pfle_7.code='7' left outer join prlpaydetailstransfile  pfle_8 on
          prlpayroltransfile.[payroll_id]=pfle_8.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_8.[pfno]
          and pfle_8.code='8' left outer join prlpaydetailstransfile  pfle_9 on
          prlpayroltransfile.[payroll_id]=pfle_9.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_9.[pfno]
          and pfle_9.code='9' left outer join prlpaydetailstransfile  pfle_10 on
          prlpayroltransfile.[payroll_id]=pfle_10.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_10.[pfno]
          and pfle_10.code='10' left outer join prlpaydetailstransfile  pfle_11 on
          prlpayroltransfile.[payroll_id]=pfle_11.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_11.[pfno]
          and pfle_11.code='11' left outer join prlpaydetailstransfile  pfle_12 on
          prlpayroltransfile.[payroll_id]=pfle_12.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_12.[pfno]
          and pfle_12.code='12' left outer join prlpaydetailstransfile  pfle_13 on
          prlpayroltransfile.[payroll_id]=pfle_13.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_13.[pfno]
          and pfle_13.code='13' left outer join prlpaydetailstransfile  pfle_14 on
          prlpayroltransfile.[payroll_id]=pfle_14.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_14.[pfno]
          and pfle_14.code='14' left outer join prlpaydetailstransfile  pfle_15 on
          prlpayroltransfile.[payroll_id]=pfle_15.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_15.[pfno]
          and pfle_15.code='15' left outer join prlpaydetailstransfile  pfle_16 on
          prlpayroltransfile.[payroll_id]=pfle_16.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_16.[pfno]
          and pfle_16.code='16' left outer join prlpaydetailstransfile  pfle_17 on
          prlpayroltransfile.[payroll_id]=pfle_17.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_17.[pfno]
          and pfle_17.code='17' left outer join prlpaydetailstransfile  pfle_18 on
          prlpayroltransfile.[payroll_id]=pfle_18.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_18.[pfno]
          and pfle_18.code='18' left outer join prlpaydetailstransfile  pfle_19 on
          prlpayroltransfile.[payroll_id]=pfle_19.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_19.[pfno]
          and pfle_19.code='19' left outer join prlpaydetailstransfile  pfle_20 on
          prlpayroltransfile.[payroll_id]=pfle_20.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_20.[pfno]
          and pfle_20.code='20' left outer join prlpaydetailstransfile  pfle_999999 on
          prlpayroltransfile.[payroll_id]=pfle_999999.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_999999.[pfno]
          and pfle_999999.code='999999' left outer join prlpaydetailstransfile  pfle_1000000 on
          prlpayroltransfile.[payroll_id]=pfle_1000000.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_1000000.[pfno]
          and pfle_1000000.code='1000000' left outer join prlpaydetailstransfile  pfle_1 on
          prlpayroltransfile.[payroll_id]=pfle_1.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_1.[pfno]
          and pfle_1.code='1' left outer join prlpaydetailstransfile  pfle_2 on
          prlpayroltransfile.[payroll_id]=pfle_2.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_2.[pfno]
          and pfle_2.code='2' left outer join prlpaydetailstransfile  pfle_3 on
          prlpayroltransfile.[payroll_id]=pfle_3.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_3.[pfno]
          and pfle_3.code='3' where  prlpayroltransfile.[payroll_id]='19' Group by   pfle_6.amount,  pfle_7.amount,  pfle_8.amount,  pfle_9.amount,  pfle_10.amount,  pfle_11.amount,  pfle_12.amount,  pfle_13.amount,  pfle_14.amount,  pfle_15.amount,  pfle_16.amount,  pfle_17.amount,  pfle_18.amount,  pfle_19.amount,  pfle_20.amount,  pfle_999999.amount,  pfle_1000000.amount,  pfle_1.amount,  pfle_2.amount,  pfle_3.amount, prlpayroltransfile.pfno ,
    prlemployeemaster.[bankacno],
    prlemployeemaster.[bankcode2],
    prlpayroltransfile.basicpay ,
    prlpayroltransfile.[overtime],
    prlpayroltransfile.[lateness_absent],
    prlemployeemaster.fname,
    prlemployeemaster.mname,
    prlemployeemaster.lname;
END     
Select pfno,names,Bankcode,Bankaccount,basicpay,Overtime ,A_6 ,A_7 ,A_8 ,A_9 ,A_10 ,A_11 ,A_12 ,A_13 ,A_14 ,A_15 ,A_16 ,A_17 ,A_18 ,A_19 ,A_20 ,A_999999 ,D_1 ,D_2 ,D_3 ,D_1000000 , Manhrlost ,Loans , (basicpay+ isnull(A_6,0)+ isnull(A_7,0)+ isnull(A_8,0)+ isnull(A_9,0)+ isnull(A_10,0)+ isnull(A_11,0)+ isnull(A_12,0)+ isnull(A_13,0)+ isnull(A_14,0)+ isnull(A_15,0)+ isnull(A_16,0)+ isnull(A_17,0)+ isnull(A_18,0)+ isnull(A_19,0)+ isnull(A_20,0)+ isnull(A_999999,0)+ isnull(Overtime,0) ) as allowances ,(Manhrlost+ isnull(D_1,0)+ isnull(D_2,0)+ isnull(D_3,0)+ isnull(D_1000000,0) + isnull(Loans,0)) as deductions from @masterroll 
GO
/****** Object:  StoredProcedure [dbo].[printnetpaynav]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[printnetpaynav]  @balance  float  OUTPUT
    AS
    BEGIN

    DECLARE @masterroll Table (A_1 float,A_2 float,A_3 float,A_4 float,A_5 float,A_19 float,A_25 float,A_30 float,A_31 float,A_44 float,A_103 float,A_41 float,A_98 float,A_100 float,D_101 float,D_102 float,D_99 float,D_42 float,D_43 float,D_104 float,D_105 float,D_106 float,D_107 float,D_108 float,D_109 float,D_111 float,D_112 float,D_113 float,D_114 float,D_115 float,D_116 float,D_117 float,D_45 float,D_46 float,D_47 float,D_48 float,D_49 float,D_50 float,D_51 float,D_52 float,D_53 float,D_54 float,D_55 float,D_56 float,D_57 float,D_58 float,D_59 float,D_60 float,D_61 float,D_62 float,D_63 float,D_96 float,D_97 float,D_32 float,D_33 float,D_34 float,D_35 float,D_36 float,D_37 float,D_38 float,D_39 float,D_40 float,D_27 float,D_28 float,D_29 float,D_20 float,D_21 float,D_22 float,D_23 float,D_6 float,D_7 float,D_8 float,D_9 float,D_10 float,D_13 float,D_14 float,D_15 float,D_16 float,D_17 float,D_18 float, pfno char(20),
        names varchar(max),
        basicpay float,
        Overtime float,
        Manhrlost float,
        Loans float,
        Bankaccount varchar(max),
        Bankcode varchar(20)
     );   insert into @masterroll select  pfle_1.amount,  pfle_2.amount,  pfle_3.amount,  pfle_4.amount,  pfle_5.amount,  pfle_19.amount,  pfle_25.amount,  pfle_30.amount,  pfle_31.amount,  pfle_44.amount,  pfle_103.amount,  pfle_41.amount,  pfle_98.amount,  pfle_100.amount,  pfle_101.amount,  pfle_102.amount,  pfle_99.amount,  pfle_42.amount,  pfle_43.amount,  pfle_104.amount,  pfle_105.amount,  pfle_106.amount,  pfle_107.amount,  pfle_108.amount,  pfle_109.amount,  pfle_111.amount,  pfle_112.amount,  pfle_113.amount,  pfle_114.amount,  pfle_115.amount,  pfle_116.amount,  pfle_117.amount,  pfle_45.amount,  pfle_46.amount,  pfle_47.amount,  pfle_48.amount,  pfle_49.amount,  pfle_50.amount,  pfle_51.amount,  pfle_52.amount,  pfle_53.amount,  pfle_54.amount,  pfle_55.amount,  pfle_56.amount,  pfle_57.amount,  pfle_58.amount,  pfle_59.amount,  pfle_60.amount,  pfle_61.amount,  pfle_62.amount,  pfle_63.amount,  pfle_96.amount,  pfle_97.amount,  pfle_32.amount,  pfle_33.amount,  pfle_34.amount,  pfle_35.amount,  pfle_36.amount,  pfle_37.amount,  pfle_38.amount,  pfle_39.amount,  pfle_40.amount,  pfle_27.amount,  pfle_28.amount,  pfle_29.amount,  pfle_20.amount,  pfle_21.amount,  pfle_22.amount,  pfle_23.amount,  pfle_6.amount,  pfle_7.amount,  pfle_8.amount,  pfle_9.amount,  pfle_10.amount,  pfle_13.amount,  pfle_14.amount,  pfle_15.amount,  pfle_16.amount,  pfle_17.amount,  pfle_18.amount,  prlpayroltransfile.pfno ,
    prlemployeemaster.fname +' '+prlemployeemaster.mname+' '+prlemployeemaster.lname,
    prlpayroltransfile.basicpay ,
    prlpayroltransfile.[overtime],
    prlpayroltransfile.[lateness_absent] ,
    sum(isnull([prlloantrans].[amount],0)+ isnull([prlloantrans].[interest],0)) as totalloan,
    prlemployeemaster.[bankacno],
    prlemployeemaster.[bankcode2]
   from  prlpayroltransfile 
    join prlemployeemaster on prlpayroltransfile.[pfno]=prlemployeemaster.pf_no
    left join prlloantrans on prlpayroltransfile.[payroll_id]=prlloantrans.[payroll_id]
    and prlpayroltransfile.[pfno]=prlloantrans.[pfno]  left outer join prlpaydetailstransfile  pfle_1 on
          prlpayroltransfile.[payroll_id]=pfle_1.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_1.[pfno]
          and pfle_1.code='1' left outer join prlpaydetailstransfile  pfle_2 on
          prlpayroltransfile.[payroll_id]=pfle_2.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_2.[pfno]
          and pfle_2.code='2' left outer join prlpaydetailstransfile  pfle_3 on
          prlpayroltransfile.[payroll_id]=pfle_3.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_3.[pfno]
          and pfle_3.code='3' left outer join prlpaydetailstransfile  pfle_4 on
          prlpayroltransfile.[payroll_id]=pfle_4.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_4.[pfno]
          and pfle_4.code='4' left outer join prlpaydetailstransfile  pfle_5 on
          prlpayroltransfile.[payroll_id]=pfle_5.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_5.[pfno]
          and pfle_5.code='5' left outer join prlpaydetailstransfile  pfle_19 on
          prlpayroltransfile.[payroll_id]=pfle_19.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_19.[pfno]
          and pfle_19.code='19' left outer join prlpaydetailstransfile  pfle_25 on
          prlpayroltransfile.[payroll_id]=pfle_25.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_25.[pfno]
          and pfle_25.code='25' left outer join prlpaydetailstransfile  pfle_30 on
          prlpayroltransfile.[payroll_id]=pfle_30.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_30.[pfno]
          and pfle_30.code='30' left outer join prlpaydetailstransfile  pfle_31 on
          prlpayroltransfile.[payroll_id]=pfle_31.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_31.[pfno]
          and pfle_31.code='31' left outer join prlpaydetailstransfile  pfle_44 on
          prlpayroltransfile.[payroll_id]=pfle_44.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_44.[pfno]
          and pfle_44.code='44' left outer join prlpaydetailstransfile  pfle_103 on
          prlpayroltransfile.[payroll_id]=pfle_103.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_103.[pfno]
          and pfle_103.code='103' left outer join prlpaydetailstransfile  pfle_41 on
          prlpayroltransfile.[payroll_id]=pfle_41.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_41.[pfno]
          and pfle_41.code='41' left outer join prlpaydetailstransfile  pfle_98 on
          prlpayroltransfile.[payroll_id]=pfle_98.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_98.[pfno]
          and pfle_98.code='98' left outer join prlpaydetailstransfile  pfle_100 on
          prlpayroltransfile.[payroll_id]=pfle_100.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_100.[pfno]
          and pfle_100.code='100' left outer join prlpaydetailstransfile  pfle_101 on
          prlpayroltransfile.[payroll_id]=pfle_101.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_101.[pfno]
          and pfle_101.code='101' left outer join prlpaydetailstransfile  pfle_102 on
          prlpayroltransfile.[payroll_id]=pfle_102.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_102.[pfno]
          and pfle_102.code='102' left outer join prlpaydetailstransfile  pfle_99 on
          prlpayroltransfile.[payroll_id]=pfle_99.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_99.[pfno]
          and pfle_99.code='99' left outer join prlpaydetailstransfile  pfle_42 on
          prlpayroltransfile.[payroll_id]=pfle_42.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_42.[pfno]
          and pfle_42.code='42' left outer join prlpaydetailstransfile  pfle_43 on
          prlpayroltransfile.[payroll_id]=pfle_43.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_43.[pfno]
          and pfle_43.code='43' left outer join prlpaydetailstransfile  pfle_104 on
          prlpayroltransfile.[payroll_id]=pfle_104.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_104.[pfno]
          and pfle_104.code='104' left outer join prlpaydetailstransfile  pfle_105 on
          prlpayroltransfile.[payroll_id]=pfle_105.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_105.[pfno]
          and pfle_105.code='105' left outer join prlpaydetailstransfile  pfle_106 on
          prlpayroltransfile.[payroll_id]=pfle_106.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_106.[pfno]
          and pfle_106.code='106' left outer join prlpaydetailstransfile  pfle_107 on
          prlpayroltransfile.[payroll_id]=pfle_107.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_107.[pfno]
          and pfle_107.code='107' left outer join prlpaydetailstransfile  pfle_108 on
          prlpayroltransfile.[payroll_id]=pfle_108.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_108.[pfno]
          and pfle_108.code='108' left outer join prlpaydetailstransfile  pfle_109 on
          prlpayroltransfile.[payroll_id]=pfle_109.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_109.[pfno]
          and pfle_109.code='109' left outer join prlpaydetailstransfile  pfle_111 on
          prlpayroltransfile.[payroll_id]=pfle_111.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_111.[pfno]
          and pfle_111.code='111' left outer join prlpaydetailstransfile  pfle_112 on
          prlpayroltransfile.[payroll_id]=pfle_112.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_112.[pfno]
          and pfle_112.code='112' left outer join prlpaydetailstransfile  pfle_113 on
          prlpayroltransfile.[payroll_id]=pfle_113.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_113.[pfno]
          and pfle_113.code='113' left outer join prlpaydetailstransfile  pfle_114 on
          prlpayroltransfile.[payroll_id]=pfle_114.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_114.[pfno]
          and pfle_114.code='114' left outer join prlpaydetailstransfile  pfle_115 on
          prlpayroltransfile.[payroll_id]=pfle_115.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_115.[pfno]
          and pfle_115.code='115' left outer join prlpaydetailstransfile  pfle_116 on
          prlpayroltransfile.[payroll_id]=pfle_116.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_116.[pfno]
          and pfle_116.code='116' left outer join prlpaydetailstransfile  pfle_117 on
          prlpayroltransfile.[payroll_id]=pfle_117.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_117.[pfno]
          and pfle_117.code='117' left outer join prlpaydetailstransfile  pfle_45 on
          prlpayroltransfile.[payroll_id]=pfle_45.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_45.[pfno]
          and pfle_45.code='45' left outer join prlpaydetailstransfile  pfle_46 on
          prlpayroltransfile.[payroll_id]=pfle_46.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_46.[pfno]
          and pfle_46.code='46' left outer join prlpaydetailstransfile  pfle_47 on
          prlpayroltransfile.[payroll_id]=pfle_47.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_47.[pfno]
          and pfle_47.code='47' left outer join prlpaydetailstransfile  pfle_48 on
          prlpayroltransfile.[payroll_id]=pfle_48.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_48.[pfno]
          and pfle_48.code='48' left outer join prlpaydetailstransfile  pfle_49 on
          prlpayroltransfile.[payroll_id]=pfle_49.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_49.[pfno]
          and pfle_49.code='49' left outer join prlpaydetailstransfile  pfle_50 on
          prlpayroltransfile.[payroll_id]=pfle_50.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_50.[pfno]
          and pfle_50.code='50' left outer join prlpaydetailstransfile  pfle_51 on
          prlpayroltransfile.[payroll_id]=pfle_51.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_51.[pfno]
          and pfle_51.code='51' left outer join prlpaydetailstransfile  pfle_52 on
          prlpayroltransfile.[payroll_id]=pfle_52.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_52.[pfno]
          and pfle_52.code='52' left outer join prlpaydetailstransfile  pfle_53 on
          prlpayroltransfile.[payroll_id]=pfle_53.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_53.[pfno]
          and pfle_53.code='53' left outer join prlpaydetailstransfile  pfle_54 on
          prlpayroltransfile.[payroll_id]=pfle_54.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_54.[pfno]
          and pfle_54.code='54' left outer join prlpaydetailstransfile  pfle_55 on
          prlpayroltransfile.[payroll_id]=pfle_55.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_55.[pfno]
          and pfle_55.code='55' left outer join prlpaydetailstransfile  pfle_56 on
          prlpayroltransfile.[payroll_id]=pfle_56.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_56.[pfno]
          and pfle_56.code='56' left outer join prlpaydetailstransfile  pfle_57 on
          prlpayroltransfile.[payroll_id]=pfle_57.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_57.[pfno]
          and pfle_57.code='57' left outer join prlpaydetailstransfile  pfle_58 on
          prlpayroltransfile.[payroll_id]=pfle_58.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_58.[pfno]
          and pfle_58.code='58' left outer join prlpaydetailstransfile  pfle_59 on
          prlpayroltransfile.[payroll_id]=pfle_59.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_59.[pfno]
          and pfle_59.code='59' left outer join prlpaydetailstransfile  pfle_60 on
          prlpayroltransfile.[payroll_id]=pfle_60.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_60.[pfno]
          and pfle_60.code='60' left outer join prlpaydetailstransfile  pfle_61 on
          prlpayroltransfile.[payroll_id]=pfle_61.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_61.[pfno]
          and pfle_61.code='61' left outer join prlpaydetailstransfile  pfle_62 on
          prlpayroltransfile.[payroll_id]=pfle_62.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_62.[pfno]
          and pfle_62.code='62' left outer join prlpaydetailstransfile  pfle_63 on
          prlpayroltransfile.[payroll_id]=pfle_63.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_63.[pfno]
          and pfle_63.code='63' left outer join prlpaydetailstransfile  pfle_96 on
          prlpayroltransfile.[payroll_id]=pfle_96.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_96.[pfno]
          and pfle_96.code='96' left outer join prlpaydetailstransfile  pfle_97 on
          prlpayroltransfile.[payroll_id]=pfle_97.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_97.[pfno]
          and pfle_97.code='97' left outer join prlpaydetailstransfile  pfle_32 on
          prlpayroltransfile.[payroll_id]=pfle_32.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_32.[pfno]
          and pfle_32.code='32' left outer join prlpaydetailstransfile  pfle_33 on
          prlpayroltransfile.[payroll_id]=pfle_33.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_33.[pfno]
          and pfle_33.code='33' left outer join prlpaydetailstransfile  pfle_34 on
          prlpayroltransfile.[payroll_id]=pfle_34.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_34.[pfno]
          and pfle_34.code='34' left outer join prlpaydetailstransfile  pfle_35 on
          prlpayroltransfile.[payroll_id]=pfle_35.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_35.[pfno]
          and pfle_35.code='35' left outer join prlpaydetailstransfile  pfle_36 on
          prlpayroltransfile.[payroll_id]=pfle_36.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_36.[pfno]
          and pfle_36.code='36' left outer join prlpaydetailstransfile  pfle_37 on
          prlpayroltransfile.[payroll_id]=pfle_37.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_37.[pfno]
          and pfle_37.code='37' left outer join prlpaydetailstransfile  pfle_38 on
          prlpayroltransfile.[payroll_id]=pfle_38.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_38.[pfno]
          and pfle_38.code='38' left outer join prlpaydetailstransfile  pfle_39 on
          prlpayroltransfile.[payroll_id]=pfle_39.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_39.[pfno]
          and pfle_39.code='39' left outer join prlpaydetailstransfile  pfle_40 on
          prlpayroltransfile.[payroll_id]=pfle_40.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_40.[pfno]
          and pfle_40.code='40' left outer join prlpaydetailstransfile  pfle_27 on
          prlpayroltransfile.[payroll_id]=pfle_27.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_27.[pfno]
          and pfle_27.code='27' left outer join prlpaydetailstransfile  pfle_28 on
          prlpayroltransfile.[payroll_id]=pfle_28.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_28.[pfno]
          and pfle_28.code='28' left outer join prlpaydetailstransfile  pfle_29 on
          prlpayroltransfile.[payroll_id]=pfle_29.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_29.[pfno]
          and pfle_29.code='29' left outer join prlpaydetailstransfile  pfle_20 on
          prlpayroltransfile.[payroll_id]=pfle_20.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_20.[pfno]
          and pfle_20.code='20' left outer join prlpaydetailstransfile  pfle_21 on
          prlpayroltransfile.[payroll_id]=pfle_21.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_21.[pfno]
          and pfle_21.code='21' left outer join prlpaydetailstransfile  pfle_22 on
          prlpayroltransfile.[payroll_id]=pfle_22.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_22.[pfno]
          and pfle_22.code='22' left outer join prlpaydetailstransfile  pfle_23 on
          prlpayroltransfile.[payroll_id]=pfle_23.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_23.[pfno]
          and pfle_23.code='23' left outer join prlpaydetailstransfile  pfle_6 on
          prlpayroltransfile.[payroll_id]=pfle_6.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_6.[pfno]
          and pfle_6.code='6' left outer join prlpaydetailstransfile  pfle_7 on
          prlpayroltransfile.[payroll_id]=pfle_7.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_7.[pfno]
          and pfle_7.code='7' left outer join prlpaydetailstransfile  pfle_8 on
          prlpayroltransfile.[payroll_id]=pfle_8.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_8.[pfno]
          and pfle_8.code='8' left outer join prlpaydetailstransfile  pfle_9 on
          prlpayroltransfile.[payroll_id]=pfle_9.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_9.[pfno]
          and pfle_9.code='9' left outer join prlpaydetailstransfile  pfle_10 on
          prlpayroltransfile.[payroll_id]=pfle_10.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_10.[pfno]
          and pfle_10.code='10' left outer join prlpaydetailstransfile  pfle_13 on
          prlpayroltransfile.[payroll_id]=pfle_13.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_13.[pfno]
          and pfle_13.code='13' left outer join prlpaydetailstransfile  pfle_14 on
          prlpayroltransfile.[payroll_id]=pfle_14.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_14.[pfno]
          and pfle_14.code='14' left outer join prlpaydetailstransfile  pfle_15 on
          prlpayroltransfile.[payroll_id]=pfle_15.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_15.[pfno]
          and pfle_15.code='15' left outer join prlpaydetailstransfile  pfle_16 on
          prlpayroltransfile.[payroll_id]=pfle_16.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_16.[pfno]
          and pfle_16.code='16' left outer join prlpaydetailstransfile  pfle_17 on
          prlpayroltransfile.[payroll_id]=pfle_17.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_17.[pfno]
          and pfle_17.code='17' left outer join prlpaydetailstransfile  pfle_18 on
          prlpayroltransfile.[payroll_id]=pfle_18.[payroll_id] and
          prlpayroltransfile.[pfno]=pfle_18.[pfno]
          and pfle_18.code='18' where  prlpayroltransfile.[payroll_id]='133' Group by   pfle_1.amount,  pfle_2.amount,  pfle_3.amount,  pfle_4.amount,  pfle_5.amount,  pfle_19.amount,  pfle_25.amount,  pfle_30.amount,  pfle_31.amount,  pfle_44.amount,  pfle_103.amount,  pfle_41.amount,  pfle_98.amount,  pfle_100.amount,  pfle_101.amount,  pfle_102.amount,  pfle_99.amount,  pfle_42.amount,  pfle_43.amount,  pfle_104.amount,  pfle_105.amount,  pfle_106.amount,  pfle_107.amount,  pfle_108.amount,  pfle_109.amount,  pfle_111.amount,  pfle_112.amount,  pfle_113.amount,  pfle_114.amount,  pfle_115.amount,  pfle_116.amount,  pfle_117.amount,  pfle_45.amount,  pfle_46.amount,  pfle_47.amount,  pfle_48.amount,  pfle_49.amount,  pfle_50.amount,  pfle_51.amount,  pfle_52.amount,  pfle_53.amount,  pfle_54.amount,  pfle_55.amount,  pfle_56.amount,  pfle_57.amount,  pfle_58.amount,  pfle_59.amount,  pfle_60.amount,  pfle_61.amount,  pfle_62.amount,  pfle_63.amount,  pfle_96.amount,  pfle_97.amount,  pfle_32.amount,  pfle_33.amount,  pfle_34.amount,  pfle_35.amount,  pfle_36.amount,  pfle_37.amount,  pfle_38.amount,  pfle_39.amount,  pfle_40.amount,  pfle_27.amount,  pfle_28.amount,  pfle_29.amount,  pfle_20.amount,  pfle_21.amount,  pfle_22.amount,  pfle_23.amount,  pfle_6.amount,  pfle_7.amount,  pfle_8.amount,  pfle_9.amount,  pfle_10.amount,  pfle_13.amount,  pfle_14.amount,  pfle_15.amount,  pfle_16.amount,  pfle_17.amount,  pfle_18.amount, prlpayroltransfile.pfno ,
    prlemployeemaster.[bankacno],
    prlemployeemaster.[bankcode2],
    prlpayroltransfile.basicpay ,
    prlpayroltransfile.[overtime],
    prlpayroltransfile.[lateness_absent],
    prlemployeemaster.fname,
    prlemployeemaster.mname,
    prlemployeemaster.lname;
END     
Select  @balance=sum(basicpay+ isnull(A_1,0)+ isnull(A_2,0)+ isnull(A_3,0)+ isnull(A_4,0)+ isnull(A_5,0)+ isnull(A_19,0)+ isnull(A_25,0)+ isnull(A_30,0)+ isnull(A_31,0)+ isnull(A_41,0)+ isnull(A_44,0)+ isnull(A_98,0)+ isnull(A_100,0)+ isnull(A_103,0)+ isnull(Overtime,0)  -Manhrlost  -isnull(D_6,0)  -isnull(D_7,0)  -isnull(D_8,0)  -isnull(D_9,0)  -isnull(D_10,0)  -isnull(D_13,0)  -isnull(D_14,0)  -isnull(D_15,0)  -isnull(D_16,0)  -isnull(D_17,0)  -isnull(D_18,0)  -isnull(D_20,0)  -isnull(D_21,0)  -isnull(D_22,0)  -isnull(D_23,0)  -isnull(D_27,0)  -isnull(D_28,0)  -isnull(D_29,0)  -isnull(D_32,0)  -isnull(D_33,0)  -isnull(D_34,0)  -isnull(D_35,0)  -isnull(D_36,0)  -isnull(D_37,0)  -isnull(D_38,0)  -isnull(D_39,0)  -isnull(D_40,0)  -isnull(D_42,0)  -isnull(D_43,0)  -isnull(D_45,0)  -isnull(D_46,0)  -isnull(D_47,0)  -isnull(D_48,0)  -isnull(D_49,0)  -isnull(D_50,0)  -isnull(D_51,0)  -isnull(D_52,0)  -isnull(D_53,0)  -isnull(D_54,0)  -isnull(D_55,0)  -isnull(D_56,0)  -isnull(D_57,0)  -isnull(D_58,0)  -isnull(D_59,0)  -isnull(D_60,0)  -isnull(D_61,0)  -isnull(D_62,0)  -isnull(D_63,0)  -isnull(D_96,0)  -isnull(D_97,0)  -isnull(D_99,0)  -isnull(D_101,0)  -isnull(D_102,0)  -isnull(D_104,0)  -isnull(D_105,0)  -isnull(D_106,0)  -isnull(D_107,0)  -isnull(D_108,0)  -isnull(D_109,0)  -isnull(D_111,0)  -isnull(D_112,0)  -isnull(D_113,0)  -isnull(D_114,0)  -isnull(D_115,0)  -isnull(D_116,0)  -isnull(D_117,0)  -isnull(Loans,0))  from @masterroll 

GO
/****** Object:  StoredProcedure [dbo].[printp10]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[printp10]  AS BEGIN

DECLARE @months Table (month int,frdates datetime,todates datetime);

DECLARE @payep10 Table (month int,payetax  float null);

with View_MONTH as
(
    select cast('2018-01-01' as smalldatetime) as DateValue

    union all

    select DATEADD (m,1,DateValue)  from View_MONTH  where DATEADD (m,1,DateValue) <= '2018-12-31'
)

insert into @months select DATEPART(m,DateValue),DateValue, DATEADD(m,1,DateValue)-1 from View_MONTH

DECLARE @monthno int ,@fromdates datetime , @todates datetime 

DECLARE mymonth CURSOR FOR SELECT month , frdates , todates from @months
OPEN mymonth
FETCH NEXT FROM mymonth INTO @monthno , @fromdates , @todates
WHILE @@FETCH_STATUS = 0
BEGIN


insert into @payep10 select
    @monthno as MONTH,
    sum(prlpaydetailstransfile.amount) as netpaye
    from  prlpaydetailstransfile
    where  prlpaydetailstransfile.code='1' 
        and  prlpaydetailstransfile.[payroll_id]=(select pkey from prlmrollperiods where fromdate >= @fromdates and todate <= @todates)
    
 

FETCH NEXT FROM mymonth INTO @monthno , @fromdates , @todates
END

CLOSE mymonth
DEALLOCATE mymonth

 select * from @payep10 ORDER BY [month]

END

GO
/****** Object:  StoredProcedure [dbo].[printp9]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[printp9]  AS BEGIN


DECLARE @months Table (month int,
frdates datetime,
todates datetime
);

with View_MONTH as
(
    select cast('2018-01-01' as smalldatetime) as DateValue

    union all

    select DATEADD (m,1,DateValue)  from View_MONTH  where DATEADD (m,1,DateValue) <= '2018-12-31'
)

insert into @months select DATEPART(m,DateValue),DateValue, DATEADD(m,1,DateValue)-1 from View_MONTH


DECLARE @payep9 Table
(pfno char(20) null,
month int,
payeno varchar(20) null,
names varchar(100) null,
basicPay float null,
noncash  float null,
pension  float null,
perelief  float null,
inrelief  float null,
payetax  float null
);

DECLARE @monthno int ,@fromdates datetime , @todates datetime

DECLARE mymonth CURSOR FOR SELECT month , frdates , todates from @months
OPEN mymonth
FETCH NEXT FROM mymonth INTO @monthno , @fromdates , @todates
WHILE @@FETCH_STATUS = 0
BEGIN


insert into @payep9 select
    prlemployeemaster.pf_no AS PFNO,
    @monthno as MONTH,
    prlemployeemaster.pin_no AS PINNO,
    Rtrim(prlemployeemaster.fname) +' '+Rtrim(prlemployeemaster.mname)+' '+Rtrim(prlemployeemaster.lname) AS NAMES,
    prlpayroltransfile.basicpay
    + isnull(prlpayroltransfile.allowances,0)
    + isnull(prlpayroltransfile.overtime,0)
    - isnull(prlpayroltransfile.lateness_absent,0),
    prlpayroltransfile.non_cash_benefits,
    prlpayroltransfile.pension,
    prlpayroltransfile.personalrelief,
    prlpayroltransfile.insurancerelief,
    sum(prlpaydetailstransfile.amount) as netpaye
    from  prlemployeemaster
    join  prlpayroltransfile on prlemployeemaster.pf_no = prlpayroltransfile.pfno
    join  prlpaydetailstransfile on prlpayroltransfile.pfno = prlpaydetailstransfile.[pfno]
    and   prlpayroltransfile.[payroll_id] = prlpaydetailstransfile.[payroll_id]
    where  prlpaydetailstransfile.code='1' and  prlemployeemaster.pf_no='PF003'  and  prlpayroltransfile.[payroll_id] in 
    (select pkey from prlmrollperiods where fromdate >= @fromdates and todate <= @todates)
    Group by
    prlemployeemaster.pf_no,
    prlemployeemaster.pin_no,
    prlemployeemaster.fname,
    prlemployeemaster.mname,
    prlemployeemaster.lname,
    prlpayroltransfile.basicpay,
    prlpayroltransfile.allowances,
    prlpayroltransfile.overtime,
    prlpayroltransfile.lateness_absent,
    prlpayroltransfile.non_cash_benefits,
    prlpayroltransfile.pension,
    prlpayroltransfile.personalrelief,
    prlpayroltransfile.insurancerelief



FETCH NEXT FROM mymonth INTO @monthno , @fromdates , @todates
END

CLOSE mymonth
DEALLOCATE mymonth

select * from @payep9  order by pfno, month ;

END

GO
/****** Object:  StoredProcedure [dbo].[printproducts]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[printproducts] AS BEGIN

DECLARE @masterroll Table (pfno char(20),names varchar(50),amount float,empl float);

insert into @masterroll select  prlemployeemaster.idno  as pfno ,
    Rtrim(prlemployeemaster.fname) +' '+Rtrim(prlemployeemaster.mname)+' '+Rtrim(prlemployeemaster.lname),
    sum(prlpaydetailstransfile.amount) as total,
    isnull(sum(prlpaydetailstransfile.employercontribution),0) as empl
    from  prlpaydetailstransfile join prlemployeemaster
    on prlpaydetailstransfile.[pfno] = prlemployeemaster.pf_no
    where code='2' and prlpaydetailstransfile.[payroll_id] ='19'
    Group by  prlemployeemaster.idno , prlemployeemaster.fname,
    prlemployeemaster.mname, prlemployeemaster.lname
END

select pfno,names,amount,empl from @masterroll ;
GO
/****** Object:  StoredProcedure [dbo].[printsacco]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[printsacco] AS BEGIN

DECLARE @masterroll Table (pfno char(20),names varchar(50),amount numeric(10,2),gross numeric(10,2));

insert into @masterroll 
   select  prlemployeemaster.idno ,
   prlemployeemaster.fname +' '+prlemployeemaster.mname+' '+prlemployeemaster.lname as names,
   prlloantrans.amount,
   prlstaffloans.[principal] + isnull(sum(prlloantrans.amount),0) as gross
   from prlstaffloans
   join prlemployeemaster on prlemployeemaster.pf_no=prlstaffloans.pfno
   join prlloantrans on prlloantrans.loanindex = prlstaffloans.loanindex 
   join Prlproducts on prlstaffloans.deductcode=Prlproducts.code 
   where prlloantrans.payroll_id='11' and Prlproducts.code='2' and   
            prlstaffloans.[saving]=1 
    Group by prlemployeemaster.idno, 
    prlemployeemaster.fname,
    prlemployeemaster.mname, 
    prlemployeemaster.lname, 
    prlstaffloans.[instalment],
    prlstaffloans.[principal] ,
    prlloantrans.amount
     
END

select * from @masterroll ;


GO
/****** Object:  StoredProcedure [dbo].[sendtonav]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
 CREATE PROCEDURE [dbo].[sendtonav] AS BEGIN
    
    Delete from [prlnavwebserviceupdate] where [payrollid]='133';
        
    DECLARE @code int,@description varchar(50),@Pagefilter varchar(50),@glaccountlink varchar(20),@bal_Pagefilter varchar(50)
    ,@bal_Glaccount varchar(50),@balance float,@DimensionValue varchar(20),@add bit
   
    DECLARE mymonth CURSOR FOR SELECT [code],[description],[Pagefilter],[glaccountlink],
    [bal_Pagefilter],[bal_Glaccount],[DimensionValue],[deduction] FROM [prlproducts]
    where  code !='999999'
    
            OPEN mymonth
            FETCH NEXT FROM mymonth INTO @code,@description,@Pagefilter,@glaccountlink,@bal_Pagefilter,@bal_Glaccount,@DimensionValue,@add
            WHILE @@FETCH_STATUS = 0
            BEGIN
            
                    SELECT @balance  = sum(isnull([amount],0))+ sum(isnull([employercontribution],0))
                    FROM [prlpaydetailstransfile] 
                    where [payroll_id]='133' and [code]=@code 
                                                                
                    INSERT INTO [prlnavwebserviceupdate]
                   ([payrollid],[code],[description],[amount],[pagetype],[navcode],[pagetype_balancing],[navcode_balancing],DimensionValue) VALUES 
                   ('133',@code,@description,isnull(@balance,0),@Pagefilter,@glaccountlink,@bal_Pagefilter,@bal_Glaccount,@DimensionValue)

              
            FETCH NEXT FROM mymonth INTO @code,@description,@Pagefilter,@glaccountlink,@bal_Pagefilter,@bal_Glaccount,@DimensionValue,@add
            END

    CLOSE mymonth
    DEALLOCATE mymonth

      
            EXEC [dbo].[printnetpaynav] @balance = @balance OUTPUT
                
            DECLARE mymonth CURSOR FOR SELECT [code],[description],[Pagefilter],[glaccountlink],[bal_Pagefilter],[bal_Glaccount],DimensionValue FROM [netprlproducts]
            where  code='999999'

            OPEN mymonth
            FETCH NEXT FROM mymonth INTO @code,@description,@Pagefilter,@glaccountlink,@bal_Pagefilter,@bal_Glaccount,@DimensionValue
            WHILE @@FETCH_STATUS = 0
            BEGIN
                
               INSERT INTO [prlnavwebserviceupdate]
               ([payrollid],[code],[description],[amount],[pagetype],[navcode],[pagetype_balancing],[navcode_balancing],DimensionValue) VALUES 
               ('133',999999,'NetPay',@balance,@Pagefilter,@glaccountlink,@bal_Pagefilter,@bal_Glaccount,@DimensionValue)
            
            FETCH NEXT FROM mymonth INTO @code,@description,@Pagefilter,@glaccountlink,@bal_Pagefilter,@bal_Glaccount,@DimensionValue
            END
            
            CLOSE mymonth
            DEALLOCATE mymonth

    END

GO
/****** Object:  StoredProcedure [dbo].[SetExpecteddateofreturn]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[SetExpecteddateofreturn]
    AS BEGIN 
    
    Declare @today smalldatetime, @days int 
     select @today=  cast('2018-08-17' as smalldatetime)
     select @days =5  
          
    Declare @DateName varchar(max),@CurrentDate smalldatetime
    Declare @offday int, @offmonth int ,@LastWorkingDay smalldatetime
    
    select @LastWorkingDay=DATEADD (D,@days,@today)

    Declare @workingdays table (namevar varchar(max),date datetime);

            with View_MONTH as
            (
               select cast('2018-08-17' as smalldatetime) as DateValue
               union all
               select DATEADD (D,1,DateValue) from View_MONTH where DATEADD (D,1,DateValue) <= @LastWorkingDay
            )

            insert into @workingdays select DATENAME(WEEKDAY,DateValue),DateValue from View_MONTH

            select @LastWorkingDay = max(date) from @workingdays

            DECLARE mymonth CURSOR FOR SELECT namevar ,date  from @workingdays
            OPEN mymonth
            FETCH NEXT FROM mymonth INTO @DateName ,@CurrentDate
            WHILE @@FETCH_STATUS = 0
            BEGIN
                   Declare @isworkingday bit,@holiday varchar(50)
                   
                   SELECT @isworkingday=[isworkingday] FROM [Dayoftheweeks] where [Dayoftheweek]=@DateName
                   SELECT @holiday = [name] from [prlspecialdays] where day=datepart(d,@CurrentDate) and month=datepart(m,@CurrentDate)

                   if(@isworkingday is null or @isworkingday=0)   select @LastWorkingDay = DATEADD (D,1,@LastWorkingDay) ;
                   
                   if(@holiday=1 ) select @LastWorkingDay = DATEADD (D,1,@LastWorkingDay) ;
                       

                FETCH NEXT FROM mymonth INTO @DateName ,@CurrentDate
            END
            
            CLOSE mymonth
            DEALLOCATE mymonth
             
EXECUTE [GetDOWdateofreturn] @LastWorkingDay,@outparam=@LastWorkingDay output
END
GO
/****** Object:  StoredProcedure [dbo].[ShowDayoftheweek]    Script Date: 07/04/2026 11:45:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[ShowDayoftheweek]
    AS BEGIN
Declare @me table (namevar varchar(max),date datetime);

with View_MONTH as
(
    select cast('2018-01-01' as smalldatetime) as DateValue

    union all

    select DATEADD (D,1,DateValue)  from View_MONTH  where DATEADD (D,1,DateValue) <= '2018-01-07'
)

        insert into @me select DATENAME(WEEKDAY,DateValue), DATEADD(D,1,DateValue)-1 from View_MONTH

        select namevar from  @me
        
end
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.audittrail' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'audittrail'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.companies' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'companies'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.config' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'config'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.currencies' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'currencies'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.emailsettings' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'emailsettings'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.geocode_param' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'geocode_param'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.mailgroupdetails' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'mailgroupdetails'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.mailgroups' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'mailgroups'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.periods' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'periods'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.scripts' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'scripts'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.securitygroups' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'securitygroups'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.securityroles' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'securityroles'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.securitytokens' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'securitytokens'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.systypes_1' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'systypes_1'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_SSMA_SOURCE', @value=N'wasrebs_payroll.www_users' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'www_users'
GO
