/*
Convert STRM files to VisuGps format.
This application has been developped with Ultimate++ <http://www.ultimatepp.org/>

STRM files can be found at <http://srtm.csi.cgiar.org/index.asp>

Author:
	Victor Berchet
	
History:
	Jun 18, 2007: Initial revision	
	
*/

#include "STRMconv.h"

STRMconv::STRMconv()
{
	CtrlLayout(*this, "STRM to VisuGPS converter");

	btnConvert <<= THISBACK(Convert);
	btnOpen <<= THISBACK(Open);
	
	fs.Multi(false);
    fs.Type("STRM files (*.asc)", "*.asc")
      .Type("All Files (*.*)", "*.*");	
}


void STRMconv::ComputeImg(void)
{
	ImageBuffer ib(600, 600);

	for(int y = 0; y < 600; y++) {
		RGBA *l = ib[y];
		for(int x = 0; x < 600; x++) {			
			byte height = min(raster.At(6000 * 10 * y + 10 * x) / 15 + 40, 255);
			l->a = 255;
			l->r = height;
			l->g = height;
			l->b = height;
			l++;
		}
	}
	
	img.SetImage(ib);

}

void STRMconv::Open(void)
{
    if (!fs.ExecuteOpen("Select a STRM file")) {
    	return;
    } else {
    	txt.SetText(~fs);  
    }    			
}

void STRMconv::StoreStrm(void)
{
	if (raster.GetCount() == 6000 * 6000) {
		
		String fName = txt.GetText();
		
		if (!IsNull(llCorner.x) && !IsNull(llCorner.y)) {
			fName = ToLower(GetFileDirectory(fName)) + 
					Format("strm3_%d_%d.strmb", llCorner.y, llCorner.x);
		} else {
			fName = ToLower(GetFileDirectory(fName) + 
							GetFileTitle(fName) + ".strmb");		
		}
				
		FileOut f(fName);			
		
		for (int i = 0; i < 6000 * 6000; i++) {
			int val;
			if (raster[i] <= 0) {
				val = 0;
			} else {
				val = ((raster[i] + 10) / 20) + 1;
				val = minmax(val, 0, 255);
			}
			f.Put(val);
		}
		
		f.Close();
	}
}

void STRMconv::Convert(void)
{
	String fName = txt.GetText();
	
	llCorner.SetNull();
	
	if (ToLower(GetFileExt(fName)) == ".asc") {

		if (FileExists(fName)) {
			FileIn file(fName);		
			String s;
			
			raster.Clear();
			
			// Process STRM header 
			for (int i = 0; i < 6; i++) {				
				Vector<String> fields = Split(file.GetLine(), ' ');
				if (fields.GetCount() >= 2) {				
					if (ToUpper(fields[0]) == "XLLCORNER") {
						llCorner.x = ScanInt(fields[1]);
					} else if (ToUpper(fields[0]) == "YLLCORNER") {
						llCorner.y = ScanInt(fields[1]);
					}					
				}			
			}
					
			while (!file.IsEof()) {
				int b = file.Get();
				if (b == ' ') {			
					short val = StrInt(s);
					if (val < 0) val = 0;
					raster.Add(val);
					s = "";
				} else {
					s += b;
				}
			}
			
			file.Close();
			
			ComputeImg();
			StoreStrm();
		}
	} else {
		raster.Clear();
		
		FileIn file(fName);
		
		while (!file.IsEof()) {
			raster.Add(file.Get() * 20);
		}
		
		file.Close();
		
		if (raster.GetCount() == 6000 * 6000) {
			ComputeImg();
		}		
	}
}

GUI_APP_MAIN
{
	STRMconv().Run();
}

